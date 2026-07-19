<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'name'];

    public function approvalMatrices(): HasMany
    {
        return $this->hasMany(ApprovalMatrix::class);
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }
}
