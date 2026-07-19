<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestLine extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_request_id', 'product_id', 'description', 'qty', 'price_estimate', 'subtotal'];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'price_estimate' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
