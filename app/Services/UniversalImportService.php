<?php

namespace App\Services;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\Company;
use App\Models\Contact;
use App\Models\TenantCrmRecord;
use App\Models\ImportLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;
use Exception;
use ZipArchive;
use SimpleXMLElement;

class UniversalImportService
{
    /**
     * Parse any uploaded file (XLSX, XLS, CSV, TSV, TXT, JSON) into array of rows.
     */
    public function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xlsm'])) {
            return $this->parseXlsx($file->getRealPath());
        }

        if ($extension === 'json') {
            $content = file_get_contents($file->getRealPath());
            $json = json_decode($content, true);
            if (is_array($json)) {
                return isset($json[0]) && is_array($json[0]) ? $json : [$json];
            }
            return [];
        }

        if (in_array($extension, ['sql', 'dump'])) {
            return $this->parseSqlDump($file->getRealPath());
        }

        if (in_array($extension, ['sqlite', 'sqlite3', 'db'])) {
            return $this->parseSqliteFile($file->getRealPath());
        }

        // CSV, TSV, TXT
        return $this->parseCsv($file->getRealPath());
    }

    /**
     * Parse SQL Dump file (.sql) extracting INSERT statement rows
     */
    public function parseSqlDump(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (!$content) return [];

        $rows = [];
        preg_match_all('/INSERT\s+INTO\s+[`"]?([a-zA-Z0-9_]+)[`"]?\s*(?:\(([^)]+)\))?\s*VALUES\s*(.+?);/is', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $columnList = !empty($match[2]) ? array_map(fn($c) => trim(str_replace(['`', '"', "'"], '', $c)), explode(',', $match[2])) : [];
            $valuesBlock = trim($match[3]);

            // Robust character-by-character tuple extractor handling quotes, commas and parens
            $tuples = [];
            $inString = false;
            $stringChar = '';
            $parenDepth = 0;
            $currentTuple = '';

            $len = strlen($valuesBlock);
            for ($i = 0; $i < $len; $i++) {
                $char = $valuesBlock[$i];

                if ($inString) {
                    if ($char === $stringChar && ($i === 0 || $valuesBlock[$i - 1] !== '\\')) {
                        $inString = false;
                    }
                    $currentTuple .= $char;
                } else {
                    if ($char === "'" || $char === '"') {
                        $inString = true;
                        $stringChar = $char;
                        $currentTuple .= $char;
                    } elseif ($char === '(') {
                        $parenDepth++;
                        if ($parenDepth === 1) {
                            $currentTuple = '';
                        } else {
                            $currentTuple .= $char;
                        }
                    } elseif ($char === ')') {
                        $parenDepth--;
                        if ($parenDepth === 0) {
                            $tuples[] = trim($currentTuple);
                            $currentTuple = '';
                        } else {
                            $currentTuple .= $char;
                        }
                    } else {
                        if ($parenDepth > 0) {
                            $currentTuple .= $char;
                        }
                    }
                }
            }

            foreach ($tuples as $tuple) {
                $values = str_getcsv($tuple, ',', "'", "\\");
                $values = array_map(function($v) {
                    $v = trim((string)$v);
                    if (strtoupper($v) === 'NULL') return '';
                    return trim($v, "'\"");
                }, $values);

                if (!empty($columnList)) {
                    $row = [];
                    foreach ($columnList as $idx => $col) {
                        $row[$col] = $values[$idx] ?? '';
                    }
                    if (!empty(array_filter($row, fn($x) => $x !== ''))) {
                        $rows[] = $row;
                    }
                } elseif (!empty($values)) {
                    $rows[] = $values;
                }
            }
        }

        // Fallback: if no INSERT statements matched, try CSV line parser
        if (empty($rows)) {
            return $this->parseCsv($filePath);
        }

        return $rows;
    }

    /**
     * Parse SQLite database file (.sqlite, .db) extracting records
     */
    public function parseSqliteFile(string $filePath): array
    {
        try {
            $pdo = new PDO("sqlite:{$filePath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' LIMIT 5");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($tables)) return [];

            $firstTable = $tables[0];
            $dataStmt = $pdo->query("SELECT * FROM \"{$firstTable}\" LIMIT 1000");
            return $dataStmt->fetchAll() ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Parse XLSX natively without external packages using ZipArchive + SimpleXML
     */
    public function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            // Fallback to CSV parsing in case of renamed CSV
            return $this->parseCsv($filePath);
        }

        // 1. Read shared strings
        $sharedStrings = [];
        if (($sharedStringsIndex = $zip->locateName('xl/sharedStrings.xml')) !== false) {
            $xmlString = $zip->getFromIndex($sharedStringsIndex);
            $xml = simplexml_load_string($xmlString);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read first worksheet
        $sheetXmlString = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXmlString) {
            // Try locating any sheet
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (str_starts_with($stat['name'], 'xl/worksheets/sheet')) {
                    $sheetXmlString = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $zip->close();

        if (!$sheetXmlString) {
            return [];
        }

        $sheetXml = simplexml_load_string($sheetXmlString);
        if (!$sheetXml || !isset($sheetXml->sheetData->row)) {
            return [];
        }

        $rows = [];
        $header = [];

        foreach ($sheetXml->sheetData->row as $rowIndex => $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $cellRef = (string) $cell['r'];
                $cellType = (string) $cell['t'];
                $val = (string) $cell->v;

                if ($cellType === 's' && isset($sharedStrings[(int) $val])) {
                    $val = $sharedStrings[(int) $val];
                }

                $rowData[] = trim($val);
            }

            if (empty(array_filter($rowData, fn($v) => $v !== ''))) {
                continue;
            }

            if (empty($header)) {
                $header = array_map('trim', $rowData);
            } else {
                $assocRow = [];
                foreach ($header as $colIndex => $colName) {
                    $assocRow[$colName] = $rowData[$colIndex] ?? '';
                }
                $rows[] = $assocRow;
            }
        }

        return $rows;
    }

    /**
     * Parse CSV/TSV with auto delimiter detection
     */
    public function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) return [];

        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = ',';
        if (str_contains($firstLine, "\t")) {
            $delimiter = "\t";
        } elseif (str_contains($firstLine, ';')) {
            $delimiter = ';';
        } elseif (str_contains($firstLine, '|')) {
            $delimiter = '|';
        }

        $header = null;
        $rows = [];

        while (($data = fgetcsv($handle, 4096, $delimiter)) !== false) {
            // Clean UTF-8 BOM
            if (!$header && !empty($data)) {
                $data[0] = preg_replace('/\x{FEFF}/u', '', $data[0]);
                $header = array_map('trim', $data);
                continue;
            }

            if ($header && !empty($data)) {
                $assocRow = [];
                foreach ($header as $colIndex => $colName) {
                    $assocRow[$colName] = isset($data[$colIndex]) ? trim($data[$colIndex]) : '';
                }
                if (!empty(array_filter($assocRow, fn($v) => $v !== ''))) {
                    $rows[] = $assocRow;
                }
            }
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Import rows into specified CRM Entity
     */
    public function importToEntity(string $entityType, array $rows, ?int $tenantId = null): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                // Normalize keys to lowercase/clean
                $normalized = [];
                foreach ($row as $k => $v) {
                    $cleanKey = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $k)));
                    $normalized[$cleanKey] = $v;
                }

                switch ($entityType) {
                    case 'healthcare_candidates':
                    case 'candidates':
                    case 'job_applications':
                        $this->importCandidate($normalized);
                        break;

                    case 'healthcare_vacancies':
                    case 'insurance_vacancies':
                    case 'real_estate_vacancies':
                    case 'academic_vacancies':
                    case 'vacancies':
                    case 'job_postings':
                        $this->importJobPosting($normalized);
                        break;

                    case 'policyholders':
                    case 'student_applicants':
                    case 'property_leads':
                    case 'patients':
                    case 'leads':
                    case 'contacts':
                        $this->importContact($normalized, $tenantId);
                        break;

                    case 'claims':
                    case 'deals':
                        $this->importDealOrClaim($normalized, $tenantId);
                        break;

                    case 'agents':
                    case 'doctors':
                    case 'counselors':
                    case 'staff':
                    case 'employees':
                        $this->importEmployee($normalized, $tenantId);
                        break;

                    case 'properties':
                        $this->importProperty($normalized, $tenantId);
                        break;

                    case 'crm_records':
                    default:
                        $this->importCrmRecord($normalized, $tenantId);
                        break;
                }

                $imported++;
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Row #" . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return [
            'total' => count($rows),
            'imported' => $imported,
            'failed' => $failed,
            'errors' => array_slice($errors, 0, 5),
        ];
    }

    private function importCandidate(array $data): void
    {
        $name = $data['applicant_name'] ?? $data['name'] ?? $data['candidate_name'] ?? $data['full_name'] ?? null;
        $email = $data['email'] ?? $data['email_address'] ?? ('applicant_' . Str::random(5) . '@healthcare.org');
        $phone = $data['phone'] ?? $data['phone_number'] ?? $data['mobile'] ?? '+1 (555) 000-0000';
        $experience = $data['experience'] ?? $data['experience_years'] ?? $data['clinical_experience'] ?? '3+ years';
        $stage = $data['stage'] ?? $data['pipeline_stage'] ?? $data['status'] ?? 'Applied';
        $notes = $data['notes'] ?? $data['clinical_notes'] ?? $data['specialty'] ?? null;
        $jobTitle = $data['applied_position'] ?? $data['position'] ?? $data['job_title'] ?? $data['role'] ?? 'General Medical Practitioner';
        $department = $data['department'] ?? 'General Medicine';

        if (!$name) {
            throw new Exception("Missing candidate name.");
        }

        // Find or create JobPosting
        $job = JobPosting::firstOrCreate(
            ['title' => $jobTitle],
            [
                'department' => $department,
                'location' => $data['location'] ?? 'Metro Hospital',
                'employment_type' => $data['employment_type'] ?? 'Full-Time',
                'salary_min' => (float) ($data['salary_min'] ?? 80000),
                'salary_max' => (float) ($data['salary_max'] ?? 140000),
                'status' => 'Active',
                'description' => "Position for {$jobTitle} in {$department}",
            ]
        );

        $validStages = ['Applied', 'Screening', 'Interview Scheduled', 'Offer Sent', 'Hired', 'Rejected'];
        if (!in_array($stage, $validStages)) {
            $stage = 'Applied';
        }

        JobApplication::updateOrCreate(
            ['email' => $email],
            [
                'job_posting_id' => $job->id,
                'applicant_name' => $name,
                'phone' => $phone,
                'experience_years' => $experience,
                'stage' => $stage,
                'notes' => $notes,
            ]
        );
    }

    private function importJobPosting(array $data): void
    {
        $title = $data['title'] ?? $data['job_title'] ?? $data['position'] ?? null;
        if (!$title) {
            throw new Exception("Missing job title.");
        }

        JobPosting::updateOrCreate(
            ['title' => $title],
            [
                'department' => $data['department'] ?? 'Clinical Care',
                'location' => $data['location'] ?? $data['facility'] ?? 'Main Hospital',
                'employment_type' => $data['employment_type'] ?? 'Full-Time',
                'salary_min' => (float) ($data['salary_min'] ?? $data['min_salary'] ?? 60000),
                'salary_max' => (float) ($data['salary_max'] ?? $data['max_salary'] ?? 120000),
                'status' => $data['status'] ?? 'Active',
                'description' => $data['description'] ?? $data['qualifications'] ?? null,
            ]
        );
    }

    private function importContact(array $data, ?int $tenantId): void
    {
        $name = $data['name'] ?? $data['patient_name'] ?? $data['full_name'] ?? $data['contact_name'] ?? null;
        if (!$name) {
            throw new Exception("Missing contact/patient name.");
        }

        $email = $data['email'] ?? ('contact_' . Str::random(6) . '@example.com');
        $phone = $data['phone'] ?? $data['mobile'] ?? '';

        Contact::updateOrCreate(
            ['email' => $email],
            [
                'tenant_id' => $tenantId,
                'first_name' => explode(' ', $name)[0] ?? $name,
                'last_name' => explode(' ', $name)[1] ?? '',
                'phone' => $phone,
                'lifecycle_stage' => $data['stage'] ?? $data['status'] ?? 'Lead',
                'notes' => $data['notes'] ?? $data['medical_notes'] ?? null,
            ]
        );
    }

    private function importEmployee(array $data, ?int $tenantId): void
    {
        $name = $data['name'] ?? $data['full_name'] ?? $data['doctor_name'] ?? null;
        $email = $data['email'] ?? null;
        if (!$name || !$email) {
            throw new Exception("Missing staff name or email.");
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'tenant_id' => $tenantId,
                'name' => $name,
                'password' => Hash::make('password123'),
                'is_super_admin' => false,
            ]
        );

        EmployeeProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_code' => $data['employee_code'] ?? $data['doctor_id'] ?? ('MED-' . str_pad($user->id, 4, '0', STR_PAD_LEFT)),
                'phone' => $data['phone'] ?? $data['mobile'] ?? '',
                'department' => $data['department'] ?? 'General Medicine',
                'designation' => $data['designation'] ?? $data['specialty'] ?? 'Specialist Doctor',
                'salary' => (float) ($data['salary'] ?? 95000),
                'attendance_status' => $data['attendance_status'] ?? 'Present',
                'gender' => $data['gender'] ?? 'Male',
                'state' => $data['state'] ?? '',
                'city' => $data['city'] ?? '',
            ]
        );
    }

    private function importCrmRecord(array $data, ?int $tenantId): void
    {
        TenantCrmRecord::create([
            'tenant_id' => $tenantId,
            'data' => $data,
        ]);
    }

    private function importDealOrClaim(array $data, ?int $tenantId): void
    {
        $title = $data['title'] ?? $data['claim_id'] ?? $data['deal_name'] ?? $data['policy_number'] ?? ('Record #' . Str::random(5));
        $value = (float) ($data['value'] ?? $data['claim_amount'] ?? $data['premium_amount'] ?? $data['amount'] ?? 0);
        $stage = $data['stage'] ?? $data['status'] ?? 'Open';

        \App\Models\Deal::create([
            'tenant_id' => $tenantId,
            'title' => $title,
            'value' => $value,
            'stage' => $stage,
            'notes' => $data['notes'] ?? $data['description'] ?? null,
        ]);
    }

    private function importProperty(array $data, ?int $tenantId): void
    {
        $title = $data['title'] ?? $data['property_name'] ?? ('Property #' . Str::random(4));
        \App\Models\Property::create([
            'tenant_id' => $tenantId,
            'title' => $title,
            'price' => (float) ($data['price'] ?? 0),
            'type' => $data['type'] ?? 'Apartment',
            'bhk' => $data['bhk'] ?? '3 BHK',
            'location' => $data['location'] ?? 'City Center',
            'status' => $data['status'] ?? 'Available',
        ]);
    }

    /**
     * Test an external Database Connection
     */
    public function testDatabaseConnection(array $config): array
    {
        try {
            $driver = $config['driver'] ?? 'mysql';
            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 3306;
            $database = $config['database'] ?? '';
            $username = $config['username'] ?? 'root';
            $password = $config['password'] ?? '';

            $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            // List available tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return [
                'success' => true,
                'message' => "Successfully connected to {$database} on {$host}:{$port}!",
                'tables' => $tables,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Database Connection Failed: " . $e->getMessage(),
                'tables' => [],
            ];
        }
    }

    /**
     * Import records directly from an external database table into the CRM
     */
    public function importFromDatabase(array $config, string $sourceTable, string $targetEntity, ?int $tenantId = null): array
    {
        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';

        $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
        ]);

        // Fetch rows from source table
        $stmt = $pdo->query("SELECT * FROM `{$sourceTable}` LIMIT 500");
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return [
                'total' => 0,
                'imported' => 0,
                'failed' => 0,
                'message' => "No records found in table '{$sourceTable}'.",
            ];
        }

        $result = $this->importToEntity($targetEntity, $rows, $tenantId);
        $result['message'] = "Imported {$result['imported']} records from external table '{$sourceTable}' into {$targetEntity}.";

        // Log import
        ImportLog::create([
            'tenant_id' => $tenantId,
            'import_source' => "Database ({$driver}://{$host}/{$database})",
            'entity_type' => $targetEntity,
            'file_name' => "table: {$sourceTable}",
            'total_rows' => $result['total'],
            'imported_rows' => $result['imported'],
            'failed_rows' => $result['failed'],
            'status' => $result['failed'] > 0 ? 'Partial' : 'Completed',
            'summary' => $result['message'],
        ]);

        return $result;
    }

    /**
     * Generate downloadable sample CSV template for entity
     */
    public function getSampleTemplate(string $entity): array
    {
        switch ($entity) {
            case 'healthcare_candidates':
            case 'candidates':
                return [
                    'filename' => 'sample_medical_candidates_template.csv',
                    'headers' => ['Applicant Name', 'Email', 'Phone', 'Applied Position', 'Department', 'Experience', 'Pipeline Stage', 'Notes'],
                    'sample_rows' => [
                        ['Dr. Rajesh Sharma, MD', 'rajesh.sharma@health.org', '+1 (555) 789-0123', 'Senior Consultant Cardiologist', 'Cardiology & Heart Care', '8+ years', 'Interview Scheduled', 'Board certified in Interventional Cardiology'],
                        ['Nurse Emily Watson, BSN', 'emily.w@nurseclinic.com', '+1 (555) 345-6789', 'Lead Critical Care Nurse', 'Emergency & Intensive Care (ICU)', '5 years', 'Screening', 'Level-1 Trauma & ECMO certified'],
                        ['Dr. Amit Roy, MBBS', 'amit.roy@radiocare.org', '+1 (555) 678-1234', 'Consultant Radiologist', 'Radiology & Diagnostics', '6 years', 'Offer Sent', 'Specialized in 3T MRI & CT Scans'],
                    ]
                ];

            case 'healthcare_vacancies':
            case 'vacancies':
                return [
                    'filename' => 'sample_clinical_vacancies_template.csv',
                    'headers' => ['Job Title', 'Department', 'Location', 'Employment Type', 'Min Salary', 'Max Salary', 'Description'],
                    'sample_rows' => [
                        ['Senior Consultant Neurologist', 'Neurology & Brain Care', 'City Specialty Hospital', 'Full-Time', '190000', '270000', 'Lead neuro-diagnostics, stroke unit rounds, and clinical consultations.'],
                        ['Emergency Triage Specialist', 'Emergency Medicine', 'St. Mary Trauma Center', 'Full-Time', '140000', '195000', 'Manage emergency department patient intake and acute resuscitation.'],
                        ['Pediatric ICU Registered Nurse', 'Pediatrics & Neonatal Care', 'Childrens Care Pavilion', 'Full-Time', '80000', '110000', 'Provide direct intensive care for neonatal and pediatric admissions.'],
                    ]
                ];

            case 'patients':
            case 'contacts':
            default:
                return [
                    'filename' => 'sample_patients_contacts_template.csv',
                    'headers' => ['Full Name', 'Email', 'Phone', 'Stage', 'Notes'],
                    'sample_rows' => [
                        ['Michael Chang', 'mchang@example.com', '+1 (555) 432-8765', 'Patient Lead', 'Referred for cardiology checkup'],
                        ['Sophia Garcia', 's.garcia@example.com', '+1 (555) 876-2345', 'Active Patient', 'Follow-up appointment on Friday'],
                    ]
                ];
        }
    }
}
