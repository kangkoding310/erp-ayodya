<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseRequestNumberGenerator
{
    public function generate(?Carbon $date = null): string
    {
        $date ??= now();
        $month = $date->format('m');
        $year = $date->format('Y');
        $key = "PR-{$year}-{$month}";

        $sequence = DB::selectOne(
            <<<'SQL'
                insert into number_sequences (key, last_number, created_at, updated_at)
                values (?, 1, now(), now())
                on conflict (key) do update
                    set last_number = number_sequences.last_number + 1,
                        updated_at = now()
                returning last_number
                SQL,
            [$key]
        )->last_number;

        return sprintf('PR/%s/%s/%03d', $month, $year, $sequence);
    }
}
