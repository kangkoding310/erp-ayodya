<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalMatrix extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'purchase_type_id', 'model_type', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function purchaseType(): BelongsTo
    {
        return $this->belongsTo(PurchaseType::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(ApprovalMatrixLevel::class)->orderBy('level');
    }
}
