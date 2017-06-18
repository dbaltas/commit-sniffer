<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommitTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('Commit', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sha1');
            $table->string('committer');
            $table->string('author', 100);
            $table->string('subject', 1024);
            $table->string('mergeCommit')->nullable();
            $table->boolean('selfMerged');
            $table->boolean('tracked');
            $table->datetime('author_date')->nullable();
            $table->timestamps();

            $table->index('sha1');
            $table->index('author');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('Commit');
    }
}
