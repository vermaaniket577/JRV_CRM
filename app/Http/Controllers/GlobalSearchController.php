<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim($request->query('query', ''));

        if (strlen($query) < 2) {
            return response()->json([
                'deals' => [],
                'contacts' => [],
                'companies' => [],
            ]);
        }

        $deals = Deal::where('title', 'like', "%{$query}%")
            ->take(5)
            ->get(['id', 'title', 'value'])
            ->map(function ($deal) {
                return [
                    'id' => $deal->id,
                    'title' => $deal->title,
                    'value' => (float) $deal->value,
                    'formatted_value' => '₹' . number_format($deal->value, 2),
                ];
            });

        $contacts = Contact::where('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->take(5)
            ->get(['id', 'first_name', 'last_name', 'email']);

        $companies = Company::where('name', 'like', "%{$query}%")
            ->take(5)
            ->get(['id', 'name', 'domain']);

        return response()->json([
            'deals' => $deals,
            'contacts' => $contacts,
            'companies' => $companies,
        ]);
    }
}
