<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Automatyzacje (rule engine)
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger'); // 'task.status_changed', 'invoice.overdue', etc.
            $table->json('conditions')->nullable(); // warunki
            $table->json('actions'); // akcje do wykonania
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('run_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->boolean('success');
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });

        // Webhooks wychodzące
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            // Optional: the screen that creates these asks for a URL and the
            // events, and names the entry after its host when left blank.
            $table->string('name')->nullable();
            $table->string('url');
            $table->json('events');
            $table->string('secret')->nullable();
            $table->boolean('is_active')->default(true);
            // Written by the dispatcher after every delivery. Ten failures in
            // a row switch a webhook off rather than retrying forever.
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->boolean('success')->default(false);
            $table->text('response_body')->nullable();
            $table->timestamps();
        });

        // Integracje (Slack, GitHub, Google Calendar, Jira)
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'slack', 'github', 'google_calendar', 'jira'
            $table->json('config')->nullable(); // token, webhook_url, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Własne pola (polimorficzne)
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('model'); // 'project', 'task', 'client', 'invoice'
            $table->string('name');
            $table->string('label');
            $table->enum('type', ['text', 'number', 'date', 'select', 'multiselect', 'checkbox', 'textarea']);
            $table->json('options')->nullable(); // dla select/multiselect
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();
            $table->morphs('customizable');
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Szablony emaili
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // identyfikator: 'invoice.sent', 'task.assigned', etc.
            $table->string('label'); // czytelna nazwa
            $table->string('subject');
            $table->text('body_html');
            $table->json('variables')->nullable(); // dostępne zmienne
            $table->timestamps();
        });

        // Audit log
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // 'created', 'updated', 'deleted'
            $table->string('model_type');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
        });

        // Cache table
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('automations');
    }
};
