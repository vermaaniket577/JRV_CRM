<?php

namespace App\Http\Controllers;

use App\Events\MemberVerified;
use App\Models\Member;
use App\Models\MemberDocument;
use App\Notifications\MemberVerificationApprovedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberVerificationController extends Controller
{
    public function approve(Request $request, Member $member): RedirectResponse
    {
        $member->update([
            'verification_status' => 'Verified',
            'verified_at' => now(),
            'verified_by' => auth()->id() ?? 1,
        ]);

        // Dispatch verification event & notification
        event(new MemberVerified($member));

        return redirect()->back()->with('success', "Member bio-data {$member->member_code} has been marked as Verified.");
    }

    public function reject(Request $request, Member $member): RedirectResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string'],
        ]);

        $member->update([
            'verification_status' => 'Rejected',
        ]);

        return redirect()->back()->with('error', "Member bio-data {$member->member_code} verification rejected.");
    }

    public function verifyDocument(Request $request, MemberDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:Approved,Rejected'],
            'rejection_reason' => ['nullable', 'string'],
        ]);

        $document->update([
            'status' => $validated['status'],
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'reviewed_by' => auth()->id() ?? 1,
            'reviewed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Document verification status updated.');
    }
}
