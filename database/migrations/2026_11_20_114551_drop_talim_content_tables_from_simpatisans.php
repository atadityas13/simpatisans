<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pengumuman');
        Schema::dropIfExists('pengumumen');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('app_updates');
    }

    public function down(): void
    {
        if (! Schema::hasTable('pengumuman')) {
            Schema::create('pengumuman', function (Blueprint $table) {
                $table->id();
                $table->string('judul');
                $table->text('isi');
                $table->boolean('is_active')->default(true);
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('is_active');
                $table->index('published_at');
            });
        }

        if (! Schema::hasTable('calendar_events')) {
            Schema::create('calendar_events', function (Blueprint $table) {
                $table->id();
                $table->string('title', 200);
                $table->text('note')->nullable();
                $table->date('event_date');
                $table->time('event_time')->nullable();
                $table->boolean('is_important')->default(false);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['is_active', 'event_date']);
            });
        }

        if (! Schema::hasTable('app_updates')) {
            Schema::create('app_updates', function (Blueprint $table) {
                $table->id();
                $table->string('platform', 32)->default('android');
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
    }
};
