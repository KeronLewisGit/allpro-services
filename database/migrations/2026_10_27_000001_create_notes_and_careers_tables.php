<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // system | note | status | assign | file | client (an update the client sees)
            $table->string('kind', 20)->default('system');
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 160);
            $table->string('role_type', 30)->index();
            $table->string('region', 20)->index();
            $table->string('employment_type', 20);
            $table->string('status', 20)->default('open')->index();
            $table->string('location', 160);
            $table->string('hours', 160)->nullable();
            $table->unsignedSmallInteger('openings')->default(1);
            $table->text('summary');
            $table->text('duties')->nullable();
            $table->text('requirements')->nullable();
            $table->date('closes_on')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('job_posting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role_type', 30)->index();
            $table->string('region', 20)->index();
            $table->string('status', 20)->default('new')->index();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('phone', 30);
            $table->string('phone_digits', 20)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('county', 30)->nullable();
            $table->string('experience', 20);
            $table->string('right_to_work', 20);
            $table->string('employment_type', 20)->nullable();
            $table->text('availability')->nullable();
            $table->boolean('drives')->nullable();
            $table->date('available_from')->nullable();
            // Security roles: the applicant's own PSA licence, checked by HR on the PSA register.
            $table->string('psa_licence_number', 20)->nullable();
            $table->date('psa_licence_expires_on')->nullable();
            $table->timestamp('psa_checked_at')->nullable();
            $table->foreignId('psa_checked_by')->nullable()->constrained('users')->nullOnDelete();
            // Tracked by HR only; never collected through the website.
            $table->string('vetting_status', 20)->default('not_started');
            $table->timestamp('vetting_updated_at')->nullable();
            $table->timestamp('rtw_checked_at')->nullable();
            $table->foreignId('rtw_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('cv_name', 200)->nullable();
            $table->string('cv_mime', 120)->nullable();
            $table->unsignedInteger('cv_size')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no', 20)->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('role_type', 30)->index();
            $table->string('region', 20)->index();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->date('started_on')->nullable();
            $table->boolean('active')->default(true);
            $table->string('psa_licence_number', 20)->nullable();
            $table->date('psa_licence_expires_on')->nullable()->index();
            // 0 nothing sent, 1 expiring notice sent, 2 expired notice sent. Reset when the date changes.
            $table->unsignedTinyInteger('psa_reminder_stage')->default(0);
            $table->timestamp('psa_reminded_at')->nullable();
            $table->string('vetting_status', 20)->default('not_started');
            $table->date('vetting_cleared_on')->nullable();
            $table->date('rtw_checked_on')->nullable();
            $table->foreignId('job_application_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('status', 20)->default('new')->index();
            $table->string('topic', 20);
            $table->string('name', 120);
            $table->string('organisation', 160)->nullable();
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->text('message');
            $table->string('source_url', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('staff_members');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('notes');
    }
};
