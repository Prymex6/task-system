<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['owner', 'admin', 'manager', 'member', 'guest']);
            $table->string('action'); // e.g. 'projects.create', 'invoices.view', etc.
            $table->boolean('allowed')->default(true);
            $table->timestamps();

            $table->unique(['role', 'action']);
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->enum('workspace_role', ['admin', 'manager', 'member', 'guest'])->default('member');
            $table->string('token', 64)->unique();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('role_permissions');
    }
};
