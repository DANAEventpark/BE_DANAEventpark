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
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->default(100)->after('status');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->after('capacity');
            $table->string('image')->nullable()->after('category_id');
            $table->dateTime('registration_deadline')->nullable()->after('image');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            //
        });
    }
};
