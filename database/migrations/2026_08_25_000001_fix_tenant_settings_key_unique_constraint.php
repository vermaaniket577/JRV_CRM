<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(DB::select("SHOW INDEXES FROM tenant_settings"))->pluck('Key_name')->all();

        if (in_array('tenant_settings_key_unique', $indexes)) {
            Schema::table('tenant_settings', function (Blueprint $table) {
                $table->dropUnique('tenant_settings_key_unique');
            });
        }

        if (!in_array('tenant_settings_tenant_id_key_unique', $indexes)) {
            Schema::table('tenant_settings', function (Blueprint $table) {
                $table->unique(['tenant_id', 'key']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            //
        });
    }
};
