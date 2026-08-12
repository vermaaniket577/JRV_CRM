<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteIntegrationController extends Controller
{
    public function index(Request $request): Response
    {
        $appUrl = $request->getSchemeAndHttpHost();
        $apiKey = 'jsm_live_sec_' . md5(auth()->id() . 'secret_salt_2026');

        $embedScriptCode = '<script src="' . $appUrl . '/embed/widget.js" data-tenant-key="' . $apiKey . '" async></script>';
        $iframeCode = '<iframe src="' . $appUrl . '/embed/register?api_key=' . $apiKey . '" width="100%" height="650" frameborder="0" style="border:none; border-radius: 16px;"></iframe>';

        $apiDocs = [
            'base_url' => $appUrl . '/api/v1',
            'endpoints' => [
                [
                    'method' => 'POST',
                    'path' => '/members/register',
                    'description' => 'Submit new member bio-data from external website form.',
                ],
                [
                    'method' => 'GET',
                    'path' => '/members/search',
                    'description' => 'Retrieve bio-data candidates matching search criteria.',
                ],
            ],
        ];

        return Inertia::render('Tenant/Settings/Integration/Index', [
            'apiKey' => $apiKey,
            'embedScriptCode' => $embedScriptCode,
            'iframeCode' => $iframeCode,
            'apiDocs' => $apiDocs,
            'appUrl' => $appUrl,
        ]);
    }
}
