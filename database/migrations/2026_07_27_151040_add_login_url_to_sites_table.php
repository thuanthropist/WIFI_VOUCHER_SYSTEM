<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            // The router/controller's own captive-portal login form action
            // (e.g. "http://10.10.0.1/login" for a Mikrotik hotspot). When
            // set, the portal auto-submits the voucher as username/password
            // here instead of showing the code to the customer.
            $table->string('login_url')->nullable()->after('device_vendor');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('login_url');
        });
    }
};
