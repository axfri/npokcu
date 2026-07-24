<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory;

    public const PURCHASE_PROCESSING = 'processing';

    public const PURCHASE_ACTIVE = 'active';

    public const PURCHASE_EXPIRED = 'expired';

    public const PURCHASE_CANCELLED = 'cancelled';

    public const PURCHASE_DELIVERY_FAILED = 'delivery_failed';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
        'duration_days',
        'starts_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'duration_days' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function proxyDelivery(): HasOne
    {
        return $this->hasOne(ProxyDelivery::class);
    }

    public function purchaseStatus(): string
    {
        if ($this->order?->order_status === Order::STATUS_CANCELLED) {
            return self::PURCHASE_CANCELLED;
        }

        if (
            $this->order?->order_status === Order::STATUS_FAILED
            || $this->proxyDelivery?->effectiveStatus() === ProxyDelivery::STATUS_FAILED
        ) {
            return self::PURCHASE_DELIVERY_FAILED;
        }

        return match ($this->proxyDelivery?->effectiveStatus()) {
            ProxyDelivery::STATUS_ACTIVE => self::PURCHASE_ACTIVE,
            ProxyDelivery::STATUS_EXPIRED => self::PURCHASE_EXPIRED,
            default => self::PURCHASE_PROCESSING,
        };
    }

    public function purchaseStatusLabel(): string
    {
        return match ($this->purchaseStatus()) {
            self::PURCHASE_ACTIVE => 'Активен',
            self::PURCHASE_EXPIRED => 'Срок закончился',
            self::PURCHASE_CANCELLED => 'Отменён',
            self::PURCHASE_DELIVERY_FAILED => 'Ошибка выдачи',
            default => 'Обрабатывается',
        };
    }

    public function purchaseStatusVariant(): string
    {
        return match ($this->purchaseStatus()) {
            self::PURCHASE_ACTIVE => 'success',
            self::PURCHASE_EXPIRED, self::PURCHASE_CANCELLED => 'neutral',
            self::PURCHASE_DELIVERY_FAILED => 'danger',
            default => 'warning',
        };
    }

    public function isPurchaseActive(): bool
    {
        return $this->purchaseStatus() === self::PURCHASE_ACTIVE;
    }

    public function isPurchaseProcessing(): bool
    {
        return $this->purchaseStatus() === self::PURCHASE_PROCESSING;
    }

    public function remainingDays(): int
    {
        return $this->proxyDelivery?->remainingDays() ?? 0;
    }
}
