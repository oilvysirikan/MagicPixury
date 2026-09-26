<?php

declare(strict_types=1);

namespace App\Pixury\Creative\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Creation extends Model
{
    use HasFactory;

    protected $table = 'pixury_creations';

    // Valid status values for the state machine
    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'user_id',
        'template_id',
        'type',
        'status',
        'prompt',
        'negative_prompt',
        'options',
        'product_image_path',
        'result_url',
        'thumb_url',
        'fal_request_id',
        'fal_entity',
        'fal_result',
        'error_message',
        'credits_used',
        'generation_time',
        'resolution',
        'aspect_ratio',
        'completed_at',
    ];

    protected $casts = [
        'options'      => 'array',
        'fal_result'   => 'array',
        'credits_used' => 'decimal:4',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isTerminal(): bool
    {
        return $this->isCompleted() || $this->isFailed();
    }

    /**
     * Can this creation be retried?
     * Only failed generations can be retried, and only if not exceeded max attempts.
     */
    public function canRetry(): bool
    {
        return $this->isFailed();
    }

    /**
     * Transition to processing state.
     * Returns true if transition was successful.
     */
    public function markProcessing(): bool
    {
        return $this->update(['status' => self::STATUS_PROCESSING]) > 0;
    }

    /**
     * Mark as completed with result data.
     */
    public function markCompleted(array $data): bool
    {
        return $this->update(array_merge([
            'status'       => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ], $data)) > 0;
    }

    /**
     * Mark as failed with error message.
     * Idempotent - only updates if not already completed.
     */
    public function markFailed(string $error): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        return $this->update([
            'status'        => self::STATUS_FAILED,
            'error_message' => $error,
        ]) > 0;
    }

    /**
     * Reset for retry (called by controller when user retries).
     * Clears error and resets to pending.
     */
    public function resetForRetry(): bool
    {
        if (!$this->isFailed()) {
            return false;
        }

        return $this->update([
            'status'         => self::STATUS_PENDING,
            'error_message'  => null,
            'fal_request_id' => null,
            'fal_entity'     => null,
            'fal_result'     => null,
        ]) > 0;
    }

    public function getResultUrlAttribute(): ?string
    {
        return $this->attributes['result_url'] ?? null;
    }

    public function getThumbUrlAttribute(): ?string
    {
        return $this->attributes['thumb_url']
            ?? $this->attributes['result_url']
            ?? null;
    }
}