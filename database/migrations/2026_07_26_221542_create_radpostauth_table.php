<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $connection = 'radius';

    public function up(): void
    {
        Schema::connection($this->connection)->create('radpostauth', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 64);
            $table->string('pass', 64)->default('');
            $table->string('reply', 32)->default('');
            $table->timestamp('authdate')->useCurrent();
            $table->string('class', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('radpostauth');
    }
};
