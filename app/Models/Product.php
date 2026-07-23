<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'price', 'user_price', 'partner_price', 'tax_percentage', 'type', 'product_category_id',
        'account_type', 'coa', 'coa_parent', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'user_price' => 'decimal:2',
            'partner_price' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'account_type');
    }

    public function currencyRef(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency');
    }
}
