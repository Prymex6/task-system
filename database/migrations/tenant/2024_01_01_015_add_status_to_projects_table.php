<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('planning')->after('project_status_id');
            // Alias: code uses 'due_date', migration used 'deadline'
            if (!Schema::hasColumn('projects', 'due_date')) {
                $table->date('due_date')->nullable()->after('deadline');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumnIfExists('status');
            $table->dropColumnIfExists('due_date');
        });
    }
};
