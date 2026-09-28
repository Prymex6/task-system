<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leady CRM
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('city')->nullable();
            $table->string('industry')->nullable();
            $table->decimal('value', 12, 2)->nullable(); // szacunkowa wartość
            $table->string('currency', 3)->default('PLN');
            $table->enum('status', ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'])->default('new');
            $table->enum('source', ['website', 'referral', 'cold-call', 'social', 'email', 'other'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });

        // Aktywności na leadzie
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['call', 'email', 'meeting', 'note', 'task']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });

        // Etapy pipeline dealów (konfigurowalne)
        Schema::create('deal_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->default('#6366f1');
            $table->unsignedInteger('order')->default(0);
            $table->decimal('win_probability', 5, 2)->default(0); // %
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();
        });

        // Deale / szanse sprzedaży
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->decimal('value', 12, 2)->nullable();
            $table->string('currency', 3)->default('PLN');
            $table->date('expected_close_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('order')->default(0); // kolejność w kolumnie pipeline
            $table->timestamps();
        });

        // Notatki (polimorficzne)
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('notable'); // client, lead, deal, project, task
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        // Tagi
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->default('#6b7280');
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->primary(['tag_id', 'taggable_type', 'taggable_id']);
        });

        // Aktywności CRM (polimorficzne)
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject'); // client, lead, deal
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // 'created', 'updated', 'status_changed', etc.
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('deal_stages');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
    }
};
