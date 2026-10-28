<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('status', 20)->default('new')->index();
            $table->string('region', 20)->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('services');
            $table->string('sector', 20);
            $table->string('organisation', 160);
            $table->string('contact_name', 120);
            $table->string('contact_role', 120)->nullable();
            $table->string('email');
            $table->string('phone', 30);
            $table->string('phone_digits', 20)->nullable()->index();
            $table->string('site_name', 160);
            $table->string('site_address', 255);
            $table->string('town', 80);
            $table->string('county', 30);
            $table->string('eircode', 10)->nullable();
            $table->unsignedSmallInteger('site_count')->default(1);
            $table->unsignedInteger('floor_area_m2')->nullable();
            $table->text('cover')->nullable();
            $table->date('start_on')->nullable();
            $table->date('survey_on')->index();
            $table->string('survey_window', 20)->default('any');
            $table->boolean('is_tender')->default(false);
            $table->string('tender_reference', 80)->nullable();
            $table->date('tender_closes_on')->nullable();
            $table->text('message')->nullable();
            $table->dateTime('survey_at')->nullable();
            $table->decimal('quoted_value', 12, 2)->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->string('source_url', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('quote_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name', 200);
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_files');
        Schema::dropIfExists('quote_requests');
    }
};
