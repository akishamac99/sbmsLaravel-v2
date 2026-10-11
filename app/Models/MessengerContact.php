<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessengerContact extends Model
{
    protected $table = 'messenger_contacts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'link_code_expires_at' => 'datetime',
            'linked_at' => 'datetime',
            'last_inbound_at' => 'datetime',
        ];
    }

    // One person's row. $type is customer or staff.
    public function scopeForUser($query, string $type, int $userId)
    {
        return $query->where('user_type', $type)->where('userID', $userId);
    }

    public function getIsLinkedAttribute(): bool
    {
        return filled($this->psid);
    }

    // Meta allows a proactive message only within 24 hours of the person's last message to the Page.
    public function getCanReceiveAttribute(): bool
    {
        return $this->is_linked
            && $this->last_inbound_at !== null
            && $this->last_inbound_at->gt(now()->subHours(24));
    }
}
