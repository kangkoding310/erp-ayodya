<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['account_type', 'currency']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('account_type')->nullable()->after('coa_parent')->constrained('account_types')->nullOnDelete();
            $table->foreignId('currency')->nullable()->after('account_type')->constrained('currencies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['account_type']);
            $table->dropForeign(['currency']);
            $table->dropColumn(['account_type', 'currency']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('account_type')->nullable()->after('coa_parent');
            $table->string('currency')->nullable()->after('account_type');
        });
    }
};
