<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_updates', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 32)->default('android')->index();
            $table->unsignedInteger('latest_version_code')->default(1);
            $table->string('latest_version_name', 40)->default('1.0.0');
            $table->unsignedInteger('minimum_version_code')->default(1);
            $table->string('title', 160)->default('Update Ta\'lim tersedia');
            $table->text('message')->nullable();
            $table->text('changelog')->nullable();
            $table->string('play_store_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_updates');
    }
};
