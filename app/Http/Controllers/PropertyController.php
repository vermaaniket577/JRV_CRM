<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(Request $request): Response
    {
        $isSubdomain = app()->bound('is_tenant_subdomain') && app('is_tenant_subdomain');
        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $tenantId = ($isSubdomain && $currentTenant) ? $currentTenant->id : (session('tenant_id') ?? $request->user()?->tenant_id);

        $query = Property::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } elseif ($isSubdomain) {
            $query->whereRaw('1 = 0');
        }

        if ($search = $request->query('query')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('locality', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%");
            });
        }

        if ($listingType = $request->query('listing_type')) {
            $query->where('listing_type', $listingType);
        }

        if ($propertyType = $request->query('property_type')) {
            $query->where('property_type', $propertyType);
        }

        if ($bedrooms = $request->query('bedrooms')) {
            if ($bedrooms === '4+') {
                $query->where('bedrooms', '>=', 4);
            } else {
                $query->where('bedrooms', (int) $bedrooms);
            }
        }

        if ($furnishing = $request->query('furnishing_status')) {
            $query->where('furnishing_status', $furnishing);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($city = $request->query('city')) {
            $query->where('city', $city);
        }

        $perPage = (int) $request->query('per_page', 12);
        $properties = $query->latest()->paginate($perPage)->withQueryString();

        $propCountBase = Property::query();
        if ($tenantId) {
            $propCountBase->where('tenant_id', $tenantId);
        } elseif ($isSubdomain) {
            $propCountBase->whereRaw('1 = 0');
        }

        $metrics = [
            'total_properties' => (clone $propCountBase)->count(),
            'for_rent' => (clone $propCountBase)->where('listing_type', 'For Rent')->count(),
            'for_sale' => (clone $propCountBase)->where('listing_type', 'For Sale')->count(),
            'available_units' => (clone $propCountBase)->where('status', 'Available')->count(),
            'rented_or_sold' => (clone $propCountBase)->whereIn('status', ['Rented Out', 'Sold'])->count(),
        ];

        return Inertia::render('RealEstate/Properties', [
            'properties' => $properties,
            'metrics' => $metrics,
            'filters' => $request->only(['query', 'listing_type', 'property_type', 'bedrooms', 'furnishing_status', 'status', 'city', 'per_page']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isSubdomain = app()->bound('is_tenant_subdomain') && app('is_tenant_subdomain');
        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $tenantId = ($isSubdomain && $currentTenant) ? $currentTenant->id : (session('tenant_id') ?? $request->user()?->tenant_id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'listing_type' => ['required', 'string', 'in:For Rent,For Sale,Lease,PG / Co-living'],
            'property_type' => ['required', 'string', 'in:Apartment,Villa / House,Studio Flat,Commercial Office,Retail Shop,Penthouse'],
            'price' => ['required', 'numeric', 'min:0'],
            'security_deposit' => ['nullable', 'numeric', 'min:0'],
            'bedrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['required', 'integer', 'min:1', 'max:20'],
            'carpet_area_sqft' => ['nullable', 'integer', 'min:50'],
            'furnishing_status' => ['required', 'string', 'in:Fully Furnished,Semi-Furnished,Unfurnished'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'locality' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_phone' => ['nullable', 'string', 'max:50'],
            'owner_email' => ['nullable', 'email', 'max:255'],
            'amenities' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:Available,Under Offer,Rented Out,Sold'],
            'image_url' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $count = Property::where('tenant_id', $tenantId)->count() + 1;
        $code = 'PROP-' . strtoupper(substr($validated['property_type'], 0, 3)) . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        Property::create(array_merge($validated, [
            'tenant_id' => $tenantId,
            'property_code' => $code,
            'status' => $validated['status'] ?? 'Available',
        ]));

        return redirect()->back()->with('success', 'Property listing created successfully.');
    }

    public function updateStatus(Request $request, Property $property): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:Available,Under Offer,Rented Out,Sold'],
        ]);

        $property->update($validated);

        return redirect()->back()->with('success', "Property status updated to {$validated['status']}.");
    }

    public function export(Request $request)
    {
        $isSubdomain = app()->bound('is_tenant_subdomain') && app('is_tenant_subdomain');
        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $tenantId = ($isSubdomain && $currentTenant) ? $currentTenant->id : (session('tenant_id') ?? $request->user()?->tenant_id);

        $query = Property::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } elseif ($isSubdomain) {
            $query->whereRaw('1 = 0');
        }

        $properties = $query->latest()->get();
        $filename = 'properties_rentals_export_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($properties) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Property Code', 'Title', 'Listing Type', 'Property Type', 'Price (INR/Month or Total)',
                'Security Deposit', 'Bedrooms (BHK)', 'Bathrooms', 'Carpet Area (sqft)', 'Furnishing Status',
                'Locality', 'City', 'State', 'Owner Name', 'Owner Phone', 'Status'
            ]);

            foreach ($properties as $p) {
                fputcsv($file, [
                    $p->property_code,
                    $p->title,
                    $p->listing_type,
                    $p->property_type,
                    $p->price,
                    $p->security_deposit ?? 0,
                    $p->bedrooms,
                    $p->bathrooms,
                    $p->carpet_area_sqft ?? 0,
                    $p->furnishing_status,
                    $p->locality ?? '',
                    $p->city,
                    $p->state,
                    $p->owner_name ?? '',
                    $p->owner_phone ?? '',
                    $p->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"sample_properties_template.csv\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Title', 'Listing Type', 'Property Type', 'Price', 'Security Deposit',
                'Bedrooms', 'Bathrooms', 'Carpet Area Sqft', 'Furnishing Status',
                'Locality', 'City', 'State', 'Owner Name', 'Owner Phone'
            ]);
            fputcsv($file, [
                'Luxury 2 BHK Sea Facing Apartment', 'For Rent', 'Apartment', '45000', '150000',
                '2', '2', '950', 'Fully Furnished',
                'Bandra West', 'Mumbai', 'Maharashtra', 'Vikram Malhotra', '+91 9876543210'
            ]);
            fputcsv($file, [
                'Modern 3 BHK Independent Villa with Garden', 'For Sale', 'Villa / House', '18500000', '0',
                '3', '3', '2400', 'Semi-Furnished',
                'Whitefield', 'Bengaluru', 'Karnataka', 'Rajesh Sharma', '+91 9876543211'
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $tenantId = session('tenant_id') ?? $request->user()?->tenant_id;
        if (!$tenantId && app()->bound('current_tenant') && app('current_tenant')) {
            $tenantId = app('current_tenant')->id;
        }
        if (!$tenantId) {
            $tenantId = \App\Models\Tenant::where('subdomain', 'like', '%unlockrentals%')->value('id') ?? \App\Models\Tenant::value('id');
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $importedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue;

            $title = trim($row[0]);
            $listingType = isset($row[1]) && in_array(trim($row[1]), ['For Rent', 'For Sale', 'Lease', 'PG / Co-living']) ? trim($row[1]) : 'For Rent';
            $propertyType = isset($row[2]) && in_array(trim($row[2]), ['Apartment', 'Villa / House', 'Studio Flat', 'Commercial Office', 'Retail Shop', 'Penthouse']) ? trim($row[2]) : 'Apartment';
            $price = isset($row[3]) && is_numeric(trim($row[3])) ? (float) trim($row[3]) : 25000.00;
            $deposit = isset($row[4]) && is_numeric(trim($row[4])) ? (float) trim($row[4]) : 50000.00;
            $bedrooms = isset($row[5]) && is_numeric(trim($row[5])) ? (int) trim($row[5]) : 2;
            $bathrooms = isset($row[6]) && is_numeric(trim($row[6])) ? (int) trim($row[6]) : 2;
            $area = isset($row[7]) && is_numeric(trim($row[7])) ? (int) trim($row[7]) : 850;
            $furnishing = isset($row[8]) && in_array(trim($row[8]), ['Fully Furnished', 'Semi-Furnished', 'Unfurnished']) ? trim($row[8]) : 'Semi-Furnished';
            $locality = isset($row[9]) ? trim($row[9]) : 'Central Area';
            $city = isset($row[10]) ? trim($row[10]) : 'Mumbai';
            $state = isset($row[11]) ? trim($row[11]) : 'Maharashtra';
            $ownerName = isset($row[12]) ? trim($row[12]) : 'Property Owner';
            $ownerPhone = isset($row[13]) ? trim($row[13]) : '+91 9800000000';

            $count = Property::count() + 1;
            $code = 'PROP-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $propertyType) ?: 'PRP', 0, 3)) . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            Property::create([
                'tenant_id' => $tenantId,
                'property_code' => $code,
                'title' => $title,
                'listing_type' => $listingType,
                'property_type' => $propertyType,
                'price' => $price,
                'security_deposit' => $deposit,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'carpet_area_sqft' => $area,
                'furnishing_status' => $furnishing,
                'locality' => $locality,
                'city' => $city,
                'state' => $state,
                'owner_name' => $ownerName,
                'owner_phone' => $ownerPhone,
                'status' => 'Available',
            ]);

            \App\Models\TenantCrmRecord::create([
                'tenant_id' => $tenantId,
                'title' => $title,
                'contact_name' => $ownerName,
                'phone' => $ownerPhone,
                'status' => 'Available',
                'value' => $price,
                'custom_data' => [
                    'property_code' => $code,
                    'property_title' => $title,
                    'property_type' => $propertyType,
                    'listing_type' => $listingType,
                    'price' => $price,
                    'bedrooms' => $bedrooms,
                    'carpet_area_sqft' => $area,
                    'locality' => $locality,
                    'city' => $city,
                    'owner_name' => $ownerName,
                    'owner_phone' => $ownerPhone,
                ],
            ]);

            $importedCount++;
        }

        fclose($handle);

        return redirect()->back()->with('success', "{$importedCount} real estate listings successfully imported.");
    }
}
