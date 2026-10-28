<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->string('access_key', 40);
            $table->string('organisation', 160);
            $table->string('site_name', 160);
            $table->string('region', 20)->index();
            $table->string('address', 255)->nullable();
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->text('services')->nullable();
            $table->dateTime('link_expires_at')->nullable();
            $table->dateTime('last_opened_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('client_account_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('category', 20);
            $table->string('priority', 20)->index();
            $table->string('status', 20)->default('new')->index();
            $table->string('reporter_name', 120);
            $table->string('reporter_email')->nullable();
            $table->string('reporter_phone', 30)->nullable();
            $table->string('location', 160)->nullable();
            $table->text('description');
            $table->dateTime('response_due_at')->index();
            $table->dateTime('resolve_due_at')->index();
            $table->dateTime('responded_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name', 200);
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_photos');
        Schema::dropIfExists('service_tickets');
        Schema::dropIfExists('client_accounts');
    }
};
