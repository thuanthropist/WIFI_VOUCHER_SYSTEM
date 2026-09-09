<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('issued_by')->nullable()->after('gateway')->constrained('users')->nullOnDelete();
            $table->string('note')->nullable()->after('meta');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issued_by');
            $table->dropColumn('note');
        });
    }
};
