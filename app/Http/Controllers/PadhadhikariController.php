<?php

namespace App\Http\Controllers;

use App\Models\Padhadhikari;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PadhadhikariController extends Controller
{
    public function index(): Response
    {
        $officers = Padhadhikari::latest()->paginate(12);

        $metrics = [
            'total_officers' => Padhadhikari::count(),
            'active_regions' => Padhadhikari::distinct('region')->count('region'),
            'national_board_members' => Padhadhikari::where('designation', 'like', '%President%')
                ->orWhere('designation', 'like', '%Secretary%')
                ->count(),
            'renewals_due' => 0,
        ];

        return Inertia::render('PadhadhikariDirectory', [
            'officers' => $officers,
            'metrics' => $metrics,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'caste_group' => ['required', 'string'],
            'region' => ['required', 'string'],
            'contact_number' => ['required', 'string'],
            'email' => ['required', 'email'],
            'term_start' => ['required', 'date'],
            'term_end' => ['nullable', 'date'],
            'responsibilities' => ['nullable', 'string'],
        ]);

        Padhadhikari::create(array_merge($validated, [
            'status' => 'Active',
        ]));

        return redirect()->back()->with('success', "Office Bearer (Padhadhikari) {$validated['name']} registered successfully.");
    }
}
