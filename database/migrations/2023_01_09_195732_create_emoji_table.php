<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emoji', function (Blueprint $table) {
            $table->id();
            $table->string("code");
            $table->string("desctiption")->nullable();
            $table->enum('type', ['emotion', 'reaction', 'emoji'])->index()->default('emoji');
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
        Schema::dropIfExists('emoji');
    }
};
