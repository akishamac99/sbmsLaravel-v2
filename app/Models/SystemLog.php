<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $table = 'system_logs';
    protected $primaryKey = 'logID';

    const UPDATED_AT = null;

    protected $fillable = ['user_type', 'userID', 'action', 'details', 'ip_address'];

    // Write one activity entry. $type is customer, staff, owner or manager.
    // A failure here is reported but never stops the page the user is on.
    public static function record(string $type, int $userId, string $action, ?string $details = null): void
    {
        try {
            static::create([
                'user_type' => $type,
                'userID' => $userId,
                'action' => $action,
                'details' => $details,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
