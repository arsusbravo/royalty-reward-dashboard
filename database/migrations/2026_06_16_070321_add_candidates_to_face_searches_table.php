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
        Schema::table('face_searches', function (Blueprint $table) {
            // longText, not json — the Plesk server's MariaDB predates JSON
            // type support. Eloquent's array cast handles encode/decode either way.
            $table->longText('candidates')->nullable()->after('similarity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('face_searches', function (Blueprint $table) {
            $table->dropColumn('candidates');
        });
    }
};
