<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // All review columns already defined in create_reviews_table migration
        Schema::table('reviews', function (Blueprint $table) {
            // no-op
        });

    }
};
