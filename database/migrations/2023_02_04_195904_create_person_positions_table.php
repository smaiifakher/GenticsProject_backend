<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('person_positions', function (Blueprint $table) {
            $table->id();
            $table->string('oid',255);
            $table->string('person');
            $table->string('pos_x')->nullable();
            $table->string('pos_y')->nullable();
            $table->string('raw_data',255)->nullable();
            $table->string('timestamp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('person_positions');
    }
};
