<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'banks';

    protected $fillable = ['bank_name', 'account_number', 'account_name'];

    public function purchasePayments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }
}
