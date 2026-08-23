<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Member;
use App\Models\NavigationItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim($request->query('query', ''));
        $category = $request->query('category', 'all');

        if (strlen($query) < 2) {
            return response()->json([
                'modules' => [],
                'members' => [],
                'deals' => [],
                'tasks' => [],
                'contacts' => [],
                'employees' => [],
            ]);
        }

        $results = [
            'modules' => [],
            'members' => [],
            'deals' => [],
            'tasks' => [],
            'contacts' => [],
            'employees' => [],
        ];

        // 1. Search CRM Navigation Modules & Pages
        if ($category === 'all' || $category === 'modules') {
            $modules = NavigationItem::where('is_enabled', true)
                ->where(function ($q) use ($query) {
                    $q->where('label', 'like', "%{$query}%")
                      ->orWhere('key', 'like', "%{$query}%");
                })
                ->get();

            $seenKeys = [];
            $uniqueModules = [];
            foreach ($modules as $item) {
                $cleanRoute = strtolower(trim($item->route ?? ''));
                $cleanLabel = strtolower(trim($item->label ?? ''));
                if (!empty($cleanRoute) && !isset($seenKeys[$cleanRoute]) && !isset($seenKeys[$cleanLabel])) {
                    $seenKeys[$cleanRoute] = true;
                    $seenKeys[$cleanLabel] = true;
                    $uniqueModules[] = [
                        'id' => $item->id,
                        'title' => $item->label,
                        'subtitle' => 'Module / System Page',
                        'route' => $item->route,
                        'icon' => $item->icon,
                    ];
                }
            }
            $results['modules'] = array_slice($uniqueModules, 0, 5);
        }

        // 2. Search Biodata / Candidates
        if (($category === 'all' || $category === 'members') && Schema::hasTable('members')) {
            $results['members'] = Member::where(function ($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('caste', 'like', "%{$query}%")
                      ->orWhere('city', 'like', "%{$query}%")
                      ->orWhere('education_level', 'like', "%{$query}%")
                      ->orWhere('education_field', 'like', "%{$query}%");
                })
                ->take(5)
                ->get()
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'title' => "{$m->first_name} {$m->last_name}",
                    'subtitle' => "{$m->gender} • {$m->caste} • {$m->city}",
                    'route' => '/matrimonial/directory',
                    'status' => $m->verification_status ?? 'Active',
                ]);
        }

        // 3. Search Deals
        if (($category === 'all' || $category === 'deals') && Schema::hasTable('deals')) {
            $results['deals'] = Deal::where('title', 'like', "%{$query}%")
                ->take(5)
                ->get()
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'subtitle' => '₹' . number_format((float) ($d->value ?? 0), 2) . ' • ' . ($d->stage ?? 'Lead'),
                    'route' => '/deals',
                ]);
        }

        // 4. Search Tasks & Reminders
        if (($category === 'all' || $category === 'tasks') && Schema::hasTable('tasks')) {
            $results['tasks'] = Task::where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%");
                })
                ->take(5)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'subtitle' => 'Priority: ' . ($t->priority ?? 'Medium') . ' • Due: ' . ($t->due_date ? date('M d', strtotime($t->due_date)) : 'No date'),
                    'route' => '/task-dashboard',
                ]);
        }

        // 5. Search Contacts
        if (($category === 'all' || $category === 'contacts') && Schema::hasTable('contacts')) {
            $results['contacts'] = Contact::where(function ($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->take(5)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'title' => "{$c->first_name} {$c->last_name}",
                    'subtitle' => $c->email ?? $c->phone ?? 'Contact',
                    'route' => '/contacts',
                ]);
        }

        // 6. Search Employees & Users
        if ($category === 'all' || $category === 'employees') {
            $results['employees'] = User::where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->take(5)
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'title' => $u->name,
                    'subtitle' => $u->email,
                    'route' => '/employee-management',
                ]);
        }

        return response()->json($results);
    }
}
