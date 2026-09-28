<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
        });

        DB::statement("ALTER TABLE contracts MODIFY status ENUM('draft', 'sent', 'signed', 'active', 'expired', 'cancelled', 'terminated') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY status ENUM('draft', 'sent', 'signed', 'expired', 'cancelled') NOT NULL DEFAULT 'draft'");

        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable(false)->change();
        });
    }
};
