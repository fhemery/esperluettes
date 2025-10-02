<?php

namespace App\Domains\Message\Private\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageDelivery extends Model
{
    use HasFactory;

    protected $table = 'message_deliveries';

    protected $fillable = [
        'message_id',
        'user_id',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'message_id' => 'integer',
        'user_id' => 'integer',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function recipient()
    {
        // No explicit relationship to avoid cross-domain FK
        // Use user_id directly when needed
        return null;
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
