<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $connection = 'radius';

    public function up(): void
    {
        Schema::connection($this->connection)->create('radacct', function (Blueprint $table) {
            $table->bigIncrements('radacctid');
            $table->string('acctsessionid', 64);
            $table->string('acctuniqueid', 32)->unique();
            $table->string('username', 64)->nullable();
            $table->string('groupname', 64)->nullable();
            $table->string('realm', 64)->nullable();
            $table->string('nasipaddress', 15);
            $table->string('nasportid', 32)->nullable();
            $table->string('nasporttype', 32)->nullable();
            $table->dateTime('acctstarttime')->nullable();
            $table->dateTime('acctupdatetime')->nullable();
            $table->dateTime('acctstoptime')->nullable();
            $table->integer('acctinterval')->nullable();
            $table->unsignedBigInteger('acctsessiontime')->nullable();
            $table->string('acctauthentic', 32)->nullable();
            $table->string('connectinfo_start', 50)->nullable();
            $table->string('connectinfo_stop', 50)->nullable();
            $table->unsignedBigInteger('acctinputoctets')->nullable();
            $table->unsignedBigInteger('acctoutputoctets')->nullable();
            $table->string('calledstationid', 50);
            $table->string('callingstationid', 50);
            $table->string('acctterminatecause', 32)->nullable();
            $table->string('servicetype', 32)->nullable();
            $table->string('framedprotocol', 32)->nullable();
            $table->string('framedipaddress', 15)->nullable();
            $table->string('framedipv6address', 45)->nullable();
            $table->string('framedipv6prefix', 45)->nullable();
            $table->string('framedinterfaceid', 44)->nullable();
            $table->string('delegatedipv6prefix', 45)->nullable();

            $table->index('username');
            $table->index('nasipaddress');
            $table->index('acctsessionid');
            $table->index('acctstarttime');
            $table->index('acctstoptime');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('radacct');
    }
};
