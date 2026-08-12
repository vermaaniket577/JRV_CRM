<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use App\Models\TenantSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NavigationCustomizerController extends Controller
{
    public function index(): Response
    {
        $items = NavigationItem::orderBy('display_order')->get();

        $businessSettings = [
            'business_name' => TenantSetting::getByKey('business_name', 'JSM CRM'),
            'business_icon' => TenantSetting::getByKey('business_icon', '♥'),
            'brand_color' => TenantSetting::getByKey('brand_color', 'red'),
        ];

        return Inertia::render('Tenant/Settings/Navigation/Index', [
            'navigationItems' => $items,
            'businessSettings' => $businessSettings,
            'availableIcons' => [
                'HomeIcon', 'UserIcon', 'UserGroupIcon', 'SignalIcon', 
                'DocumentTextIcon', 'ArrowPathIcon', 'AcademicCapIcon', 
                'BriefcaseIcon', 'GlobeAltIcon', 'ShieldCheckIcon', 
                'Square3Stack3DIcon', 'ClipboardDocumentListIcon', 'BuildingOfficeIcon',
                'ChartBarIcon', 'HeartIcon', 'CodeBracketIcon', 'EnvelopeIcon',
                'LinkIcon', 'StarIcon', 'FolderIcon', 'MegaphoneIcon', 'Cog6ToothIcon'
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_name' => ['nullable', 'string', 'max:255'],
            'business_icon' => ['nullable', 'string', 'max:50'],
            'brand_color' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array'],
            'items.*.id' => ['nullable'],
            'items.*.key' => ['required', 'string', 'max:255'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.icon' => ['required', 'string', 'max:255'],
            'items.*.route' => ['required', 'string', 'max:255'],
            'items.*.is_enabled' => ['required', 'boolean'],
            'items.*.display_order' => ['nullable', 'integer'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer'],
        ]);

        if (array_key_exists('business_name', $validated)) {
            TenantSetting::setByKey('business_name', $validated['business_name']);
        }
        if (array_key_exists('business_icon', $validated)) {
            TenantSetting::setByKey('business_icon', $validated['business_icon']);
        }
        if (array_key_exists('brand_color', $validated)) {
            TenantSetting::setByKey('brand_color', $validated['brand_color']);
        }

        if (!empty($validated['deleted_ids'])) {
            NavigationItem::whereIn('id', $validated['deleted_ids'])->delete();
        }

        foreach ($validated['items'] as $itemData) {
            if (!empty($itemData['id'])) {
                NavigationItem::where('id', $itemData['id'])->update([
                    'key' => $itemData['key'],
                    'label' => $itemData['label'],
                    'icon' => $itemData['icon'],
                    'route' => $itemData['route'],
                    'is_enabled' => $itemData['is_enabled'],
                    'display_order' => $itemData['display_order'] ?? 0,
                ]);
            } else {
                NavigationItem::create([
                    'key' => $itemData['key'],
                    'label' => $itemData['label'],
                    'icon' => $itemData['icon'],
                    'route' => $itemData['route'],
                    'is_enabled' => $itemData['is_enabled'],
                    'display_order' => $itemData['display_order'] ?? 0,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Navigation menu & settings updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $item = NavigationItem::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Navigation menu item deleted successfully.');
    }
}
