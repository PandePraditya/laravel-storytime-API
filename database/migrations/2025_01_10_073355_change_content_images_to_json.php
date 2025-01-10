<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->json('content_images')->change();
        });
    }

    public function down()
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->text('content_images')->change();
        });
    }
};
