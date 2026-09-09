<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $connection = 'radius';

    public function up(): void
    {
        Schema::connection($this->connection)->create('radcheck', function (Blueprint $table) {
            $table->id('id');
            $table->string('username', 64)->default('');
            $table->string('attribute', 64)->default('');
            $table->string('op', 2)->default('==');
            $table->string('value', 253)->default('');

            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('radcheck');
    }
};
