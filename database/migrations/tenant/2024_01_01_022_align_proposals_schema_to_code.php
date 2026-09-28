<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();

            if (!Schema::hasColumn('proposals', 'number')) {
                $table->string('number', 50)->nullable()->after('title');
            }
            if (!Schema::hasColumn('proposals', 'notes')) {
                $table->text('notes')->nullable()->after('body');
            }
            if (!Schema::hasColumn('proposals', 'total_net')) {
                $table->decimal('total_net', 12, 2)->nullable()->after('total');
            }
            if (!Schema::hasColumn('proposals', 'total_tax')) {
                $table->decimal('total_tax', 12, 2)->nullable()->after('total_net');
            }
            if (!Schema::hasColumn('proposals', 'total_gross')) {
                $table->decimal('total_gross', 12, 2)->nullable()->after('total_tax');
            }
            if (!Schema::hasColumn('proposals', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('total_gross');
            }
            if (!Schema::hasColumn('proposals', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });

        DB::statement('UPDATE proposals SET total_gross = total WHERE total_gross IS NULL AND total IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            foreach (['rejection_reason', 'issue_date', 'total_gross', 'total_tax', 'total_net', 'notes', 'number'] as $column) {
                if (Schema::hasColumn('proposals', $column)) {
                    $table->dropColumn($column);
                }
            }
            $table->foreignId('created_by')->nullable(false)->change();
        });
    }
};
