<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Member;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $userName = auth()->user()?->name ?? 'Sarah Connor';

        $totalMembers = Member::count();
        $activeUsers = $totalMembers > 0 ? $totalMembers : 18765;
        $totalInstalled = 4876;
        $totalDownloads = 678;

        $invoices = [
            ['id' => 'INV-1704200000121', 'category' => 'Premium Bio-data', 'price' => '₹5,100.00', 'status' => 'Paid', 'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            ['id' => 'INV-1704200000122', 'category' => 'Counselor Matching', 'price' => '₹2,100.00', 'status' => 'Out of Date', 'badge_class' => 'bg-rose-100 text-rose-800 border-rose-300'],
            ['id' => 'INV-1704200000123', 'category' => 'VIP Verification', 'price' => '₹7,500.00', 'status' => 'In Progress', 'badge_class' => 'bg-amber-100 text-amber-800 border-amber-300'],
            ['id' => 'INV-1704200000124', 'category' => 'Community Feature', 'price' => '₹3,200.00', 'status' => 'Paid', 'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            ['id' => 'INV-1704200000125', 'category' => 'Biodata Promotion', 'price' => '₹1,500.00', 'status' => 'Out of Date', 'badge_class' => 'bg-rose-100 text-rose-800 border-rose-300'],
        ];

        $topApplications = [
            ['name' => 'Google Translate', 'icon' => '🌐', 'tag' => 'Official', 'tag_bg' => 'bg-slate-100 text-slate-700', 'rating' => 5, 'reviews' => '9.83k reviews'],
            ['name' => 'Drive Sync', 'icon' => '📁', 'tag' => 'Smart', 'tag_bg' => 'bg-amber-100 text-amber-800', 'rating' => 5, 'reviews' => '8.41k reviews'],
            ['name' => 'Dropbox Vault', 'icon' => '📦', 'tag' => 'Secure', 'tag_bg' => 'bg-sky-100 text-sky-800', 'rating' => 5, 'reviews' => '7.20k reviews'],
            ['name' => 'Evernote Notes', 'icon' => '🐘', 'tag' => 'Popular', 'tag_bg' => 'bg-red-100 text-red-800', 'rating' => 5, 'reviews' => '6.90k reviews'],
            ['name' => 'GitHub Code', 'icon' => '🐙', 'tag' => 'Dev', 'tag_bg' => 'bg-emerald-100 text-emerald-800', 'rating' => 5, 'reviews' => '5.50k reviews'],
        ];

        $countries = [
            ['name' => 'India', 'flag' => '🇮🇳', 'downloads' => '45.6k', 'percentage' => 78.5],
            ['name' => 'Germany', 'flag' => '🇩🇪', 'downloads' => '22.1k', 'percentage' => 55.2],
            ['name' => 'United States', 'flag' => '🇺🇸', 'downloads' => '18.4k', 'percentage' => 42.8],
            ['name' => 'United Kingdom', 'flag' => '🇬🇧', 'downloads' => '12.9k', 'percentage' => 31.0],
            ['name' => 'Vietnam', 'flag' => '🇻🇳', 'downloads' => '8.7k', 'percentage' => 20.4],
        ];

        $topAuthors = [
            ['name' => 'Jayden Smith', 'role' => 'Counselor', 'likes' => '9.2k'],
            ['name' => 'Lucia Beauty', 'role' => 'Matchmaker', 'likes' => '8.1k'],
            ['name' => 'Larsen Vesta', 'role' => 'Community Admin', 'likes' => '7.4k'],
        ];

        return Inertia::render('AppDashboard', [
            'userName' => $userName,
            'metrics' => [
                'active_users' => number_format($activeUsers),
                'total_installed' => number_format($totalInstalled),
                'total_downloads' => number_format($totalDownloads),
            ],
            'invoices' => $invoices,
            'topApplications' => $topApplications,
            'countries' => $countries,
            'topAuthors' => $topAuthors,
        ]);
    }
}
