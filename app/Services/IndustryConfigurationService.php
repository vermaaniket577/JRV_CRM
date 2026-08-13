<?php

namespace App\Services;

use App\Models\Industry;
use App\Models\IndustryModule;
use App\Models\NavigationItem;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\TenantSetting;

class IndustryConfigurationService
{
    /**
     * Provision a tenant's CRM configuration based on their selected industry.
     * Creates navigation items, pipelines, and pipeline stages from industry templates.
     */
    public function provision(Tenant $tenant): void
    {
        $industry = $tenant->industry;

        if (!$industry) {
            return;
        }

        $this->provisionNavigation($tenant, $industry);
        $this->provisionPipeline($tenant, $industry);
        $this->provisionSettings($tenant, $industry);
    }

    /**
     * Create navigation items from industry module templates.
     */
    private function provisionNavigation(Tenant $tenant, Industry $industry): void
    {
        // Remove existing navigation items for this tenant
        NavigationItem::where('tenant_id', $tenant->id)->delete();

        $modules = IndustryModule::where('industry_id', $industry->id)
            ->orderBy('display_order')
            ->get();

        // If no industry-specific modules, use generic defaults
        if ($modules->isEmpty()) {
            $modules = collect($this->getGenericModules());
        }

        foreach ($modules as $index => $module) {
            $modKey = is_array($module) ? $module['module_key'] : $module->module_key;
            NavigationItem::updateOrCreate(
                ['tenant_id' => $tenant->id, 'key' => $modKey],
                [
                    'label' => is_array($module) ? $module['label'] : $module->label,
                    'icon' => is_array($module) ? $module['icon'] : $module->icon,
                    'route' => is_array($module) ? $module['route'] : $module->route,
                    'display_order' => $index + 1,
                    'is_enabled' => true,
                ]
            );
        }
    }

    /**
     * Create pipeline and stages from industry pipeline templates.
     */
    private function provisionPipeline(Tenant $tenant, Industry $industry): void
    {
        $template = $industry->pipelineTemplates()
            ->where('is_default', true)
            ->with('stages')
            ->first();

        // If no industry-specific pipeline template, use generic
        if (!$template) {
            $this->createGenericPipeline();
            return;
        }

        $pipeline = Pipeline::create([
            'name' => $template->name,
            'is_default' => true,
            'is_active' => true,
        ]);

        foreach ($template->stages as $stageTemplate) {
            PipelineStage::create([
                'pipeline_id' => $pipeline->id,
                'name' => $stageTemplate->name,
                'display_order' => $stageTemplate->display_order,
                'win_probability' => $stageTemplate->win_probability,
                'stage_type' => $stageTemplate->stage_type,
            ]);
        }
    }

    /**
     * Set industry-related tenant settings.
     */
    private function provisionSettings(Tenant $tenant, Industry $industry): void
    {
        TenantSetting::setByKey('industry_name', $industry->name);
        TenantSetting::setByKey('industry_slug', $industry->slug);
        TenantSetting::setByKey('industry_color', $industry->color);
    }

    /**
     * Re-provision when industry is switched.
     * Preserves core CRM data (contacts, deals, etc.) but reconfigures navigation, pipeline defaults, and dashboard.
     */
    public function switchIndustry(Tenant $tenant, Industry $newIndustry): void
    {
        $tenant->update([
            'industry_id' => $newIndustry->id,
        ]);

        $tenant->refresh();

        $this->provisionNavigation($tenant, $newIndustry);
        $this->provisionSettings($tenant, $newIndustry);

        // Note: We do NOT delete existing pipelines/deals when switching industries.
        // We only create the new industry default pipeline if none exists.
        $existingPipeline = Pipeline::where('is_default', true)->first();
        if (!$existingPipeline) {
            $this->provisionPipeline($tenant, $newIndustry);
        }
    }

    /**
     * Generic modules for industries without specific configuration.
     */
    private function getGenericModules(): array
    {
        return [
            ['module_key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ChartBarIcon', 'route' => '/'],
            ['module_key' => 'leads', 'label' => 'Leads', 'icon' => 'UserIcon', 'route' => '/leads'],
            ['module_key' => 'contacts', 'label' => 'Contacts', 'icon' => 'BookOpenIcon', 'route' => '/contacts'],
            ['module_key' => 'companies', 'label' => 'Companies', 'icon' => 'BuildingOfficeIcon', 'route' => '/companies'],
            ['module_key' => 'deals', 'label' => 'Deals', 'icon' => 'Square3Stack3DIcon', 'route' => '/deals'],
            ['module_key' => 'tasks', 'label' => 'Tasks', 'icon' => 'ClipboardDocumentListIcon', 'route' => '/tasks'],
            ['module_key' => 'reports', 'label' => 'Reports', 'icon' => 'ChartPieIcon', 'route' => '/reports'],
            ['module_key' => 'settings', 'label' => 'Settings', 'icon' => 'Cog6ToothIcon', 'route' => '/tenant/settings/navigation'],
        ];
    }

    /**
     * Create a generic sales pipeline.
     */
    private function createGenericPipeline(): void
    {
        $pipeline = Pipeline::create([
            'name' => 'Standard Sales Pipeline',
            'is_default' => true,
            'is_active' => true,
        ]);

        $stages = [
            ['name' => 'New', 'display_order' => 1, 'win_probability' => 10, 'stage_type' => 'open'],
            ['name' => 'Contacted', 'display_order' => 2, 'win_probability' => 25, 'stage_type' => 'open'],
            ['name' => 'Qualified', 'display_order' => 3, 'win_probability' => 40, 'stage_type' => 'open'],
            ['name' => 'Proposal', 'display_order' => 4, 'win_probability' => 60, 'stage_type' => 'open'],
            ['name' => 'Negotiation', 'display_order' => 5, 'win_probability' => 80, 'stage_type' => 'open'],
            ['name' => 'Won', 'display_order' => 6, 'win_probability' => 100, 'stage_type' => 'won'],
            ['name' => 'Lost', 'display_order' => 7, 'win_probability' => 0, 'stage_type' => 'lost'],
        ];

        foreach ($stages as $stage) {
            PipelineStage::create(array_merge($stage, ['pipeline_id' => $pipeline->id]));
        }
    }
}
