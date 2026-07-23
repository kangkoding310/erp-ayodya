<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $names = ['IDR', 'USD', 'SGD', 'CNY'];

        $now = now();
        DB::table('currencies')->insert(array_map(fn ($name) => [
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], $names));
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
