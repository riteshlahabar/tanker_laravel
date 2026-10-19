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

    /**
     * An attempt failed but the job has tries left.
     *
     * The row stays pending on purpose: with a retry budget of roughly fifty
     * minutes, marking it Failed on the first stumble would have the log
     * announcing a dead message for the best part of an hour while the queue
     * was still going to deliver it. The reason is recorded anyway, so a
     * pending row with attempts on it reads as "still trying, last error was
     * this".
     */
    public function markRetrying(string $response): void
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'response' => $response,
            'attempts' => $this->attempts + 1,
        ]);
    }

    /**
     * Put a message that never went back on the queue.
     *
     * `attempts` is deliberately *not* reset — it is the row's history, and an
     * owner looking at "6 tries" wants to know this message has been fighting,
     * not see the counter start again at one.
     */
    public function queueForResend(): void
    {
        $this->update(['status' => self::STATUS_PENDING]);
    }

    /**
     * Anything that is not already delivered can be sent again — a row stuck
     * pending because no queue worker was running needs resending just as much
     * as one the gateway refused.
     */
    public function isResendable(): bool
    {
        return $this->status !== self::STATUS_SENT;
    }
}
