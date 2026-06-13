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
    public function up(): void
{
    Schema::table('scans', function (Blueprint $table) {
        $table->string('category')->nullable()->after('confidence');
    });
}

public function down(): void
{
    Schema::table('scans', function (Blueprint $table) {
        $table->dropColumn('category');
    });
}
};
