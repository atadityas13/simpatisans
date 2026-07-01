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
        Schema::table('users', function (Blueprint $table) {
            $table->string('security_question')->nullable()->after('jabatan');
            $table->string('security_answer')->nullable()->after('security_question');
            $table->string('reset_answer_provided')->nullable()->after('security_answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['security_question', 'security_answer', 'reset_answer_provided']);
        });
    }
};
