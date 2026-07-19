<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_report_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_report_id')->constrained('expense_reports')->cascadeOnDelete();
            $table->date('expense_date');
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_report_lines');
    }
};
