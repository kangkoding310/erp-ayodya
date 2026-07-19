<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_rfqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->unique()->constrained('purchase_requests')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('sent_to_accounting_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_rfqs');
    }
};
