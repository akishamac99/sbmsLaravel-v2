<?php

namespace App\Support;

class Format
{
    // "00:30:00" -> "30 min", "01:30:00" -> "1 hr 30 min", "02:00:00" -> "2 hrs"
    public static function duration(?string $time): string
    {
        [$h, $m] = array_pad(array_map('intval', explode(':', (string) $time)), 2, 0);

        if ($h > 0 && $m > 0) {
            return "{$h} hr {$m} min";
        }

        if ($h > 0) {
            return $h . ' hr' . ($h > 1 ? 's' : '');
        }

        return "{$m} min";
    }
}
