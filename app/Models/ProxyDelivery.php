<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProxyDelivery extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_READY = 'ready';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'order_id',
        'order_item_id',
        'file_path',
        'original_filename',
        'status',
        'starts_at',
        'expires_at',
        'delivered_at',
        'download_count',
        'last_downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'delivered_at' => 'datetime',
            'download_count' => 'integer',
            'last_downloaded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function downloadLogs(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lessThanOrEqualTo(now());
    }

    public function isDownloadable(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->delivered_at !== null
            && ! $this->isExpired()
            && $this->hasSafeStoragePath();
    }

    public function effectiveStatus(): string
    {
        if ($this->status === self::STATUS_FAILED) {
            return self::STATUS_FAILED;
        }

        if ($this->isExpired() || $this->status === self::STATUS_EXPIRED) {
            return self::STATUS_EXPIRED;
        }

        if ($this->status === self::STATUS_ACTIVE && $this->delivered_at !== null) {
            return self::STATUS_ACTIVE;
        }

        return self::STATUS_PENDING;
    }

    public function remainingDays(): int
    {
        if ($this->expires_at === null || $this->isExpired()) {
            return 0;
        }

        return (int) now()
            ->startOfDay()
            ->diffInDays($this->expires_at->copy()->startOfDay(), true);
    }

    public function hasSafeStoragePath(): bool
    {
        $path = str_replace('\\', '/', $this->file_path);
        $filename = str_replace('\\', '/', $this->original_filename);
        $segments = explode('/', $path);

        if (
            $path === ''
            || $filename === ''
            || str_starts_with($path, '/')
            || str_contains($path, "\0")
            || basename($path) !== $filename
            || basename($filename) !== $filename
        ) {
            return false;
        }

        foreach ($segments as $segment) {
            if (
                $segment === ''
                || $segment === '.'
                || $segment === '..'
                || preg_match('/\A[A-Za-z0-9._-]+\z/D', $segment) !== 1
            ) {
                return false;
            }
        }

        return true;
    }

    public function matchesContext(Order $order, OrderItem $orderItem): bool
    {
        $expectedPath = $this->user_id.'/'.$order->order_number.'/'.$this->original_filename;

        return $this->hasSafeStoragePath()
            && (int) $this->user_id === (int) $order->user_id
            && (int) $this->order_id === (int) $order->getKey()
            && (int) $this->order_item_id === (int) $orderItem->getKey()
            && (int) $orderItem->order_id === (int) $order->getKey()
            && $this->file_path === $expectedPath;
    }
}
