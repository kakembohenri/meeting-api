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
        Schema::table('guest_preachers', function (Blueprint $table) {
            $table->foreignId("invitation_status")->constrained("invitation_statuses")->onDelete("cascade")->after('topic');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('guest_preachers', function (Blueprint $table) {
            //
        });
    }
};
