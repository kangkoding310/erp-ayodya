<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expense_report_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_report_id')->constrained('expense_reports')->cascadeOnDelete();
            $table->foreignId('approval_matrix_level_id')->constrained('approval_matrix_levels')->restrictOnDelete();
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_report_approvals');
    }
};
