<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseReportApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_report_id',
        'approval_matrix_level_id',
        'approver_id',
        'status',
        'remarks',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function expenseReport(): BelongsTo
    {
        return $this->belongsTo(ExpenseReport::class);
    }

    public function approvalMatrixLevel(): BelongsTo
    {
        return $this->belongsTo(ApprovalMatrixLevel::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
