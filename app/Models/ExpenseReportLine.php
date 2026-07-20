<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ExpenseReportLine extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['expense_report_id', 'expense_date', 'expense_category_id', 'project_id', 'description', 'total'];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'total' => 'decimal:2',
        ];
    }

    public function expenseReport(): BelongsTo
    {
        return $this->belongsTo(ExpenseReport::class);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'expense_category_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
