<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalMatrixLevel extends Model
{
    use HasFactory;

    protected $fillable = ['approval_matrix_id', 'level', 'approver_id', 'is_required'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function approvalMatrix(): BelongsTo
    {
        return $this->belongsTo(ApprovalMatrix::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
