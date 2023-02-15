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
        Schema::create('invitation_statuses', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->timestamp("created_at");
            $table->foreignId("created_by")->constrained("users");
            $table->timestamp("updated_at")->nullable();
            $table->foreignId("updated_by")->nullable()->constrained("users");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invitation_statuses');
    }
};
