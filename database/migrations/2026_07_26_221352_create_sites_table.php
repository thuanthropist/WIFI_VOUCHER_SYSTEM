<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('radius_nas_ip');
            $table->text('shared_secret');
            $table->string('device_vendor')->default('mikrotik');
            $table->string('nas_identifier')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('radius_nas_ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
