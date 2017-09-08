<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRevertToCommit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('Commit', function (Blueprint $table) {
            $table->boolean('revert')
                ->default(false)
                ->after('tracked');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('Commit', function (Blueprint $table) {
            $table->dropColumn('revert');
        });
    }
}
