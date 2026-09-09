<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            // Omada's External Web Portal (RADIUS) auto-login POST requires a
            // numeric authType alongside username/password. TP-Link's own
            // demo package is the source of truth for this value on a given
            // controller version — kept admin-editable rather than hardcoded
            // since it could not be independently confirmed.
            $table->string('omada_auth_type')->nullable()->after('login_url');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('omada_auth_type');
        });
    }
};
