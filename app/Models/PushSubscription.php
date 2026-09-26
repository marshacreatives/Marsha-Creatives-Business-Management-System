<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Minishlink\WebPush\Subscription as WebPushSubscription;

class PushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convert the stored row into the object the Web Push library expects.
     */
    public function toWebPushSubscription(): WebPushSubscription
    {
        return new WebPushSubscription(
            $this->endpoint,
            $this->public_key,
            $this->auth_token,
            $this->content_encoding ?: 'aes128gcm',
        );
    }
}
