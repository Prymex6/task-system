<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Firmy-klienci
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('nip', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->default('PL');
            $table->string('currency', 3)->default('PLN');
            $table->text('notes')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Contacts, who may also sign in to the client portal
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('position')->nullable(); // stanowisko
            $table->boolean('is_primary')->default(false);
            $table->boolean('portal_access')->default(false);
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('client_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Client groups
        Schema::create('client_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->default('#6366f1');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('client_group_pivot', function (Blueprint $table) {
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_group_id')->constrained()->cascadeOnDelete();
            $table->primary(['client_id', 'client_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_group_pivot');
        Schema::dropIfExists('client_groups');
        Schema::dropIfExists('client_password_reset_tokens');
        Schema::dropIfExists('client_contacts');
        Schema::dropIfExists('clients');
    }
};
