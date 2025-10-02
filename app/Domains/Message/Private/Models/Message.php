<?php

namespace App\Domains\Message\Private\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'messages';

    protected $fillable = [
        'title',
        'content',
        'sent_by_id',
        'sent_at',
        'reply_to_id',
    ];

    protected $casts = [
        'sent_by_id' => 'integer',
        'reply_to_id' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function deliveries()
    {
        return $this->hasMany(MessageDelivery::class);
    }

    public function sender()
    {
        // No explicit relationship to avoid cross-domain FK
        // Use sent_by_id directly when needed
        return null;
    }

    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }
}
