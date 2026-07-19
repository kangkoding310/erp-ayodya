<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'coa';

    protected $fillable = ['code', 'name', 'product_id', 'type'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
