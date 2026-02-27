<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFavoritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('session_id', 100)->index();
            $table->string('imdb_id', 20)->index();
            $table->string('title');
            $table->string('year', 10)->nullable();
            $table->text('poster')->nullable();
            $table->string('type', 20)->default('movie');
            $table->timestamps();

            $table->unique(['session_id', 'imdb_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('favorites');
    }
}
