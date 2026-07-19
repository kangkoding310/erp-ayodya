<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PurchaseRequest extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'code',
        'purchase_type_id',
        'project_name',
        'currency',
        'employee_id',
        'expected_date',
        'requested_by',
        'division_id',
        'status',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseStatus::class,
            'expected_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function purchaseType(): BelongsTo
    {
        return $this->belongsTo(PurchaseType::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequestLine::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PurchaseRequestMessage::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseApproval::class);
    }

    public function rfq(): HasOne
    {
        return $this->hasOne(PurchaseRfq::class);
    }
}
