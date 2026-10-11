<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'notifID';

    const UPDATED_AT = null;

    protected $fillable = ['user_type', 'userID', 'title', 'message', 'is_read'];

    public static function send(string $type, int $userId, string $title, string $message): self
    {
        $notification = static::create([
            'user_type' => $type,
            'userID' => $userId,
            'title' => $title,
            'message' => $message,
        ]);

        // Also send it to Messenger when the person has connected it. This never throws.
        try {
            app(\App\Services\MessengerService::class)->notify($type, $userId, $title, $message);
        } catch (\Throwable $e) {
            report($e);
        }

        return $notification;
    }

    public function scopeForUser($query, string $type, int $userId)
    {
        return $query->where('user_type', $type)->where('userID', $userId);
    }
}
