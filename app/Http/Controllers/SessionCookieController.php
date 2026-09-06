<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class SessionCookieController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $currentSessionId = $request->session()->getId();
        
        $sessions = [];
        if (Schema::hasTable('sessions')) {
            $query = DB::table('sessions');
            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                $query->where('id', $currentSessionId);
            }
            $dbSessions = $query->orderBy('last_activity', 'desc')->get();

            foreach ($dbSessions as $s) {
                $agent = $this->parseUserAgent($s->user_agent ?? '');
                $sessions[] = [
                    'id' => $s->id,
                    'id_preview' => substr($s->id, 0, 8) . '...',
                    'ip_address' => $s->ip_address ?? '127.0.0.1',
                    'user_agent' => $s->user_agent,
                    'browser' => $agent['browser'],
                    'platform' => $agent['platform'],
                    'device_type' => $agent['device_type'],
                    'last_activity' => date('Y-m-d H:i:s', $s->last_activity),
                    'last_activity_human' => $this->timeElapsedString($s->last_activity),
                    'is_current' => ($s->id === $currentSessionId),
                ];
            }
        }

        // If no DB sessions found or using file session driver, add current session fallback
        if (empty($sessions)) {
            $agent = $this->parseUserAgent($request->header('User-Agent', ''));
            $sessions[] = [
                'id' => $currentSessionId,
                'id_preview' => substr($currentSessionId, 0, 8) . '...',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'browser' => $agent['browser'],
                'platform' => $agent['platform'],
                'device_type' => $agent['device_type'],
                'last_activity' => date('Y-m-d H:i:s'),
                'last_activity_human' => 'Just now',
                'is_current' => true,
            ];
        }

        // Active cookies list sent to client
        $rawCookies = $request->cookies->all();
        $cookiesList = [];
        foreach ($rawCookies as $name => $val) {
            if (is_string($val) || is_numeric($val)) {
                $cookiesList[] = [
                    'name' => $name,
                    'value_preview' => strlen((string)$val) > 24 ? substr((string)$val, 0, 24) . '...' : (string)$val,
                    'category' => $this->getCookieCategory($name),
                    'is_secure' => $request->secure(),
                    'is_httponly' => in_array($name, ['laravel_session', 'XSRF-TOKEN']),
                ];
            }
        }

        $cookieConsent = [
            'consent_given' => $request->cookie('crm_cookie_consent') !== null,
            'necessary' => true,
            'analytics' => $request->cookie('crm_analytics_consent', 'true') === 'true',
            'marketing' => $request->cookie('crm_marketing_consent', 'false') === 'true',
            'preferences' => $request->cookie('crm_preferences_consent', 'true') === 'true',
            'theme' => $request->cookie('crm_theme', 'dark'),
            'sidebar_collapsed' => $request->cookie('crm_sidebar_collapsed', 'false') === 'true',
            'items_per_page' => (int) $request->cookie('crm_items_per_page', 15),
        ];

        $sessionConfig = [
            'driver' => config('session.driver', 'file'),
            'lifetime_minutes' => config('session.lifetime', 120),
            'expire_on_close' => config('session.expire_on_close', false),
            'secure_cookie' => config('session.secure', false),
            'same_site' => config('session.same_site', 'lax'),
        ];

        return Inertia::render('SessionCookieManager', [
            'sessions' => $sessions,
            'active_cookies' => $cookiesList,
            'cookie_consent' => $cookieConsent,
            'session_config' => $sessionConfig,
        ]);
    }

    public function updateConsent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'analytics' => 'required|boolean',
            'marketing' => 'required|boolean',
            'preferences' => 'required|boolean',
        ]);

        $minutesInYear = 60 * 24 * 365;

        Cookie::queue(Cookie::make('crm_cookie_consent', 'true', $minutesInYear, null, null, false, false));
        Cookie::queue(Cookie::make('crm_analytics_consent', $validated['analytics'] ? 'true' : 'false', $minutesInYear, null, null, false, false));
        Cookie::queue(Cookie::make('crm_marketing_consent', $validated['marketing'] ? 'true' : 'false', $minutesInYear, null, null, false, false));
        Cookie::queue(Cookie::make('crm_preferences_consent', $validated['preferences'] ? 'true' : 'false', $minutesInYear, null, null, false, false));

        session(['cookie_consent_updated' => true]);

        return back()->with('success', 'Cookie privacy consent preferences updated successfully.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => 'required|string|in:light,dark,system',
            'sidebar_collapsed' => 'required|boolean',
            'items_per_page' => 'required|integer|min:5|max:100',
        ]);

        $minutesInYear = 60 * 24 * 365;

        Cookie::queue(Cookie::make('crm_theme', $validated['theme'], $minutesInYear, null, null, false, false));
        Cookie::queue(Cookie::make('crm_sidebar_collapsed', $validated['sidebar_collapsed'] ? 'true' : 'false', $minutesInYear, null, null, false, false));
        Cookie::queue(Cookie::make('crm_items_per_page', (string)$validated['items_per_page'], $minutesInYear, null, null, false, false));

        session([
            'crm_theme' => $validated['theme'],
            'crm_sidebar_collapsed' => $validated['sidebar_collapsed'],
        ]);

        return back()->with('success', 'UI preferences updated and persisted in browser cookies.');
    }

    public function revokeOtherSessions(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $currentSessionId = $request->session()->getId();

        if (Schema::hasTable('sessions')) {
            $query = DB::table('sessions')->where('id', '!=', $currentSessionId);
            if ($user) {
                $query->where('user_id', $user->id);
            }
            $query->delete();
        }

        return back()->with('success', 'All other active sessions have been successfully revoked.');
    }

    public function destroySession(Request $request, string $id): RedirectResponse
    {
        $user = Auth::user();
        $currentSessionId = $request->session()->getId();

        if ($id === $currentSessionId) {
            return back()->with('error', 'Cannot terminate current active session. Use Logout instead.');
        }

        if (Schema::hasTable('sessions')) {
            $query = DB::table('sessions')->where('id', $id);
            if ($user) {
                $query->where('user_id', $user->id);
            }
            $query->delete();
        }

        return back()->with('success', 'Selected session terminated successfully.');
    }

    public function getActiveSessionsApi(Request $request)
    {
        $user = Auth::user();
        $currentSessionId = $request->session()->getId();

        $sessions = [];
        if (Schema::hasTable('sessions')) {
            $query = DB::table('sessions');
            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                $query->where('id', $currentSessionId);
            }
            $dbSessions = $query->orderBy('last_activity', 'desc')->get();

            foreach ($dbSessions as $s) {
                $agent = $this->parseUserAgent($s->user_agent ?? '');
                $sessions[] = [
                    'id' => $s->id,
                    'id_preview' => substr($s->id, 0, 8) . '...',
                    'ip_address' => $s->ip_address ?? '127.0.0.1',
                    'user_agent' => $s->user_agent,
                    'browser' => $agent['browser'],
                    'platform' => $agent['platform'],
                    'device_type' => $agent['device_type'],
                    'last_activity' => date('Y-m-d H:i:s', $s->last_activity),
                    'last_activity_human' => $this->timeElapsedString($s->last_activity),
                    'is_current' => ($s->id === $currentSessionId),
                ];
            }
        }

        if (empty($sessions)) {
            $agent = $this->parseUserAgent($request->header('User-Agent', ''));
            $sessions[] = [
                'id' => $currentSessionId,
                'id_preview' => substr($currentSessionId, 0, 8) . '...',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'browser' => $agent['browser'],
                'platform' => $agent['platform'],
                'device_type' => $agent['device_type'],
                'last_activity' => date('Y-m-d H:i:s'),
                'last_activity_human' => 'Just now',
                'is_current' => true,
            ];
        }

        return response()->json([
            'sessions' => $sessions,
            'current_session_id' => $currentSessionId,
            'count' => count($sessions),
        ]);
    }

    public function clearCookies(Request $request): RedirectResponse
    {
        $nonEssential = ['crm_analytics_consent', 'crm_marketing_consent', 'crm_theme', 'crm_sidebar_collapsed', 'crm_items_per_page'];
        foreach ($nonEssential as $cookieName) {
            Cookie::queue(Cookie::forget($cookieName));
        }

        return back()->with('success', 'Non-essential CRM preferences and tracking cookies cleared.');
    }

    private function getCookieCategory(string $name): string
    {
        if (in_array($name, ['laravel_session', 'XSRF-TOKEN', 'crm_cookie_consent'])) {
            return 'Essential & Security';
        }
        if (in_array($name, ['crm_theme', 'crm_sidebar_collapsed', 'crm_preferences_consent', 'crm_items_per_page'])) {
            return 'Preferences';
        }
        if (str_contains($name, 'analytics') || str_contains($name, 'ga') || $name === 'crm_analytics_consent') {
            return 'Analytics';
        }
        if (str_contains($name, 'marketing') || $name === 'crm_marketing_consent') {
            return 'Marketing';
        }
        return 'Application';
    }

    private function parseUserAgent(string $userAgent): array
    {
        $browser = 'Unknown Browser';
        $platform = 'Unknown OS';
        $deviceType = 'Desktop';

        if (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Apple Safari';
        } elseif (preg_match('/Edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        }

        if (preg_match('/Windows/i', $userAgent)) {
            $platform = 'Windows OS';
        } elseif (preg_match('/Macintosh|Mac OS/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
            $deviceType = 'Mobile';
        } elseif (preg_match('/iPhone|iPad/i', $userAgent)) {
            $platform = 'iOS';
            $deviceType = 'Mobile';
        }

        return [
            'browser' => $browser,
            'platform' => $platform,
            'device_type' => $deviceType,
        ];
    }

    private function timeElapsedString($timestamp): string
    {
        $etime = time() - $timestamp;
        if ($etime < 1) {
            return 'Just now';
        }

        $a = [
            365 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60  => 'month',
            24 * 60 * 60       => 'day',
            60 * 60            => 'hour',
            60                 => 'minute',
            1                  => 'second'
        ];

        foreach ($a as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                $r = round($d);
                return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
            }
        }
        return 'Just now';
    }
}
