<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $connection = 'radius';

    /**
     * FreeRADIUS 3.x compatible "nas" table (mods-config/sql/main/mysql/schema.sql).
     */
    public function up(): void
    {
        Schema::connection($this->connection)->create('nas', function (Blueprint $table) {
            $table->id('id');
            $table->string('nasname', 128);
            $table->string('shortname', 32);
            $table->string('type', 30)->default('other');
            $table->integer('ports')->nullable();
            $table->string('secret', 60)->default('secret');
            $table->string('server', 64)->nullable();
            $table->string('community', 50)->nullable();
            $table->string('description', 200)->nullable();

            $table->index('nasname');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('nas');
    }
};
