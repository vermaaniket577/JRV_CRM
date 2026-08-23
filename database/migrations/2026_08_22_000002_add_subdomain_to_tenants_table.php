<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('tenants', 'subdomain')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('subdomain', 100)->nullable()->unique()->after('slug');
                $table->string('custom_domain', 255)->nullable()->after('subdomain');
            });
        }

        // Populate subdomains for existing tenants
        $tenants = Tenant::all();
        foreach ($tenants as $t) {
            if (!$t->subdomain) {
                $clean = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($t->slug ?: $t->name));
                $clean = substr($clean, 0, 30);
                if (!$clean) $clean = 'tenant' . $t->id;
                
                // Ensure uniqueness
                $base = $clean;
                $i = 1;
                while (Tenant::where('subdomain', $clean)->where('id', '!=', $t->id)->exists()) {
                    $clean = $base . $i;
                    $i++;
                }
                $t->update(['subdomain' => $clean]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'subdomain')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn(['subdomain', 'custom_domain']);
            });
        }
    }
};
