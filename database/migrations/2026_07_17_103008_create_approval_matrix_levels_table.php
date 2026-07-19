<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_matrix_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_matrix_id')->constrained('approval_matrices')->cascadeOnDelete();
            $table->unsignedInteger('level');
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_matrix_levels');
    }
};
