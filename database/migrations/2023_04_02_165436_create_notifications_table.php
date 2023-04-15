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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->text('body');
            $table->boolean('opened')->default(false);
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("creator");
            $table->unsignedBigInteger("post_id")->nullable();
            $table->unsignedBigInteger("comment_id")->nullable();
            $table->enum('type', ['reaction', 'comment', 'post', 'friendship'])->index()->default('post');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('creator')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('comment_id')->references('id')->on('comments')->onDelete('cascade');
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
        Schema::dropIfExists('notifications');
    }
};
