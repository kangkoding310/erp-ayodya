<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resubmitting a rejected line used to wipe its prior approval rows entirely so the
 * routing could start over at the top level, which also erased the rejection (and its
 * remark) from the approval history. `cycle` lets a line go through the matrix more than
 * once — each resubmission gets the next cycle number — so old rows stay untouched as
 * history while a fresh cycle handles the new routing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_report_line_approvals', function (Blueprint $table) {
            $table->unsignedInteger('cycle')->default(1)->after('approval_matrix_level_id');

            $table->dropUnique(['expense_report_line_id', 'approval_matrix_level_id']);
            $table->unique(['expense_report_line_id', 'approval_matrix_level_id', 'cycle']);
        });
    }

    public function down(): void
    {
        Schema::table('expense_report_line_approvals', function (Blueprint $table) {
            $table->dropUnique(['expense_report_line_id', 'approval_matrix_level_id', 'cycle']);
            $table->unique(['expense_report_line_id', 'approval_matrix_level_id']);

            $table->dropColumn('cycle');
        });
    }
};
