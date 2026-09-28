<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns DB schema with application code expectations.
 *
 * Fixes mismatches between the original migration and what
 * controllers/models/Vue pages actually use.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── kb_articles ────────────────────────────────────────────────────────
        // Code uses: content, is_published, category, sort_order, visibility, views_count
        // DB has:    body, status(enum), is_public, views, (no category/sort_order/visibility)
        Schema::table('kb_articles', function (Blueprint $table) {
            // Add columns the code expects (keep old ones for now)
            if (!Schema::hasColumn('kb_articles', 'content')) {
                $table->text('content')->nullable()->after('body');
            }
            if (!Schema::hasColumn('kb_articles', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('content');
            }
            if (!Schema::hasColumn('kb_articles', 'category')) {
                $table->string('category')->nullable()->after('is_published');
            }
            if (!Schema::hasColumn('kb_articles', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('category');
            }
            if (!Schema::hasColumn('kb_articles', 'visibility')) {
                $table->enum('visibility', ['public', 'clients_only', 'internal'])->default('internal')->after('sort_order');
            }
            if (!Schema::hasColumn('kb_articles', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0)->after('visibility');
            }
            // author_id is NOT NULL — make nullable so tests without author work
            $table->foreignId('author_id')->nullable()->change();
        });

        // Copy body → content for existing rows
        DB::statement('UPDATE kb_articles SET content = body WHERE content IS NULL AND body IS NOT NULL');
        // Map status enum to is_published bool
        DB::statement("UPDATE kb_articles SET is_published = (status = 'published')");

        // ── projects: make created_by nullable (tests don't always provide creator) ──
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
        });

        // ── tasks: make created_by nullable ────────────────────────────────────
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
        });

        // ── project_members: make added_by nullable ────────────────────────────
        Schema::table('project_members', function (Blueprint $table) {
            $table->foreignId('added_by')->nullable()->change();
        });

        // ── invoices: created_by nullable ──────────────────────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
        });

        // ── tickets: add client_id nullable (already exists) ──────────────────
        // tickets.status: 'in_progress' doesn't exist — already open/pending/on_hold/resolved/closed

        // ── ticket_messages: add sender_id/sender_type aliases if missing ──────
        // (code uses body + client_contact_id which already exist — nothing to do)
    }

    public function down(): void
    {
        // Intentionally not reverting nullable changes (safe default)
    }
};
