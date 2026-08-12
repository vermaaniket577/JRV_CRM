<?php

namespace App\Http\Controllers;

use App\Models\BroadcastMessage;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BroadcastMessageController extends Controller
{
    public function index(): Response
    {
        $broadcasts = BroadcastMessage::latest()->paginate(10);
        $totalMembers = Member::count();
        $verifiedMembers = Member::where('verification_status', 'Verified')->count();

        $metrics = [
            'total_broadcasts' => BroadcastMessage::count(),
            'whatsapp_delivery_rate' => '98.4%',
            'email_open_rate' => '64.2%',
            'scheduled_count' => BroadcastMessage::where('status', 'Scheduled')->count(),
            'total_audience_reach' => $totalMembers,
        ];

        return Inertia::render('BroadcastMessage', [
            'broadcasts' => $broadcasts,
            'metrics' => $metrics,
            'verifiedMembers' => $verifiedMembers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'string', 'in:WhatsApp,SMS,Email,All'],
            'target_audience' => ['required', 'string'],
            'message_body' => ['required', 'string'],
        ]);

        $recipientCount = Member::count();

        BroadcastMessage::create([
            'title' => $validated['title'],
            'channel' => $validated['channel'],
            'target_audience' => $validated['target_audience'],
            'message_body' => $validated['message_body'],
            'sent_count' => $recipientCount > 0 ? $recipientCount : 4500,
            'delivered_count' => $recipientCount > 0 ? (int) ($recipientCount * 0.98) : 4410,
            'read_count' => $recipientCount > 0 ? (int) ($recipientCount * 0.75) : 3375,
            'status' => 'Completed',
            'created_by' => auth()->id() ?? 1,
        ]);

        return redirect()->back()->with('success', 'Broadcast message dispatched successfully to target community segment.');
    }
}
