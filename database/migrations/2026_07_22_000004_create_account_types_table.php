<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $names = ['BANK', 'AREC', 'INTR', 'OCAS', 'FASS', 'DERP', 'APAY', 'OCLY', 'LTLY', 'EQTY', 'REVE', 'COGS', 'EXPS', 'OINC', 'OEXP'];

        $now = now();
        DB::table('account_types')->insert(array_map(fn ($name) => [
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], $names));
    }

    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }
};
