<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ExpenseReport extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = ['code', 'employee_id', 'summary', 'total_expense', 'status'];

    protected function casts(): array
    {
        return [
            'status' => ExpenseStatus::class,
            'total_expense' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExpenseReportLine::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ExpenseReportApproval::class);
    }
}
