<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('crm:status', function () {
    $this->info('Multi-Tenant SaaS CRM system is online.');
});
