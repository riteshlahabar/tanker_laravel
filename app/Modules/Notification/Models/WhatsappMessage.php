<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every outbound WhatsApp message, whatever the gateway. Nothing is lost while
 * the Node service does not exist yet — the owner can read the log and the
 * messages can be replayed once it does.
 */
class WhatsappMessage extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'to', 'template', 'body', 'payload', 'status', 'attempts', 'response', 'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function markSent(string $response): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'response' => $response,
            'sent_at' => now(),
            'attempts' => $this->attempts + 1,
        ]);
    }

    public function markFailed(string $response): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'response' => $response,
            'attempts' => $this->attempts + 1,
        ]);
    }
}
