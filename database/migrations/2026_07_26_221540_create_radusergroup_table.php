<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $connection = 'radius';

    public function up(): void
    {
        Schema::connection($this->connection)->create('radusergroup', function (Blueprint $table) {
            $table->string('username', 64)->default('');
            $table->string('groupname', 64)->default('');
            $table->integer('priority')->default(1);

            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('radusergroup');
    }
};
