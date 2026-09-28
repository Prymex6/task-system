<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open', 'pending', 'in_progress', 'on_hold', 'resolved', 'closed') NOT NULL DEFAULT 'open'");

        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'opened_at')) {
                $table->timestamp('opened_at')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('tickets', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('opened_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'closed_at')) {
                $table->dropColumn('closed_at');
            }
            if (Schema::hasColumn('tickets', 'opened_at')) {
                $table->dropColumn('opened_at');
            }
        });

        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open', 'pending', 'on_hold', 'resolved', 'closed') NOT NULL DEFAULT 'open'");
    }
};
