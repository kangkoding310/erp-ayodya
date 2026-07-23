<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('account_type')->nullable()->after('product_category_id');
            $table->string('coa')->nullable()->after('account_type');
            $table->string('coa_parent')->nullable()->after('coa');
            $table->string('currency')->nullable()->after('coa_parent');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['account_type', 'coa', 'coa_parent', 'currency']);
        });
    }
};
