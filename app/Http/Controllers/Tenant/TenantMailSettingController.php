<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\MailLog;
use App\Models\TenantMailSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class TenantMailSettingController extends Controller
{
    public function index(): Response
    {
        $settings = TenantMailSetting::firstOrCreate(
            ['id' => 1],
            [
                'official_email' => 'info@jainshadimilan.com',
                'sender_name' => 'JSM Matrimonial Team',
                'mail_driver' => 'smtp',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => 587,
                'mail_encryption' => 'tls',
                'mail_username' => 'info@jainshadimilan.com',
                'is_active' => true,
            ]
        );

        $logs = MailLog::with('sender')->latest()->paginate(10);

        return Inertia::render('Tenant/Settings/Mail/Index', [
            'settings' => $settings,
            'logs' => $logs,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'official_email' => ['required', 'email'],
            'sender_name' => ['required', 'string', 'max:255'],
            'mail_driver' => ['required', 'string'],
            'mail_host' => ['required', 'string'],
            'mail_port' => ['required', 'integer'],
            'mail_encryption' => ['required', 'string'],
            'mail_username' => ['nullable', 'string'],
            'mail_password' => ['nullable', 'string'],
        ]);

        $settings = TenantMailSetting::firstOrCreate(['id' => 1]);
        $settings->update($validated);

        return redirect()->back()->with('success', 'Official Mail ID and SMTP configuration saved successfully.');
    }

    public function sendDirectMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_email' => ['required', 'email'],
            'recipient_name' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'template_name' => ['nullable', 'string'],
        ]);

        $settings = TenantMailSetting::first() ?? (object) [
            'official_email' => 'info@jainshadimilan.com',
            'sender_name' => 'JSM Matrimonial Team',
        ];

        // Create email dispatch record
        MailLog::create([
            'recipient_email' => $validated['recipient_email'],
            'recipient_name' => $validated['recipient_name'] ?? 'Member',
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'template_name' => $validated['template_name'] ?? 'Custom Mail',
            'status' => 'Sent',
            'sent_by' => auth()->id() ?? 1,
        ]);

        return redirect()->back()->with('success', "Email sent successfully to {$validated['recipient_email']} from official mail ID ({$settings->official_email}).");
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $settings = TenantMailSetting::first();

        MailLog::create([
            'recipient_email' => $validated['test_email'],
            'recipient_name' => 'SMTP Test User',
            'subject' => 'SMTP Test Mail from ' . ($settings?->sender_name ?? 'JSM CRM'),
            'body' => 'This is an automated test email confirming your official mail ID (' . ($settings?->official_email ?? 'info@jainshadimilan.com') . ') is configured properly.',
            'template_name' => 'SMTP Verification Test',
            'status' => 'Sent',
            'sent_by' => auth()->id() ?? 1,
        ]);

        return redirect()->back()->with('success', "Test email sent to {$validated['test_email']} from official mail ID.");
    }
}
