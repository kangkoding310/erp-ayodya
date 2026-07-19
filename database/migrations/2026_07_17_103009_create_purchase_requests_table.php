<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('purchase_type_id')->constrained('purchase_types')->restrictOnDelete();
            $table->string('project_name')->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->date('expected_date')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('division_id')->constrained('divisions')->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
