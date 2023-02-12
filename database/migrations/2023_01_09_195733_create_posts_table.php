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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->text("body")->nullable();
            $table->unsignedBigInteger("image_id")->nullable();
            $table->unsignedBigInteger("emotion_id")->nullable();
            $table->foreign('image_id')->references('id')->on('images')->onDelete('set null');
            $table->foreign('emotion_id')->references('id')->on('emoji')->onDelete('set null');
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
        Schema::dropIfExists('posts');
    }
};
