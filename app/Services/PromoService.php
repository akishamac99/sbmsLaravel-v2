<?php

namespace App\Services;

use App\Models\Promo;

// Promo codes. Ported from SalonSystem::validatePromo(), calculatePromoAdjustment() and applyPromo().
class PromoService
{
    // Check a code against an amount.
    // Returns ['valid' => bool, 'discount' => float, 'promo' => Promo|null].
    public function validate(?string $code, float $amount): array
    {
        $code = trim((string) $code);

        if ($code === '') {
            return ['valid' => false, 'discount' => 0.0, 'promo' => null];
        }

        // Postgres compares text exactly, so match the code ignoring upper/lower case.
        $promo = Promo::whereRaw('UPPER(promo_code) = ?', [strtoupper($code)])
            ->where('is_active', 1)
            ->whereDate('valid_from', '<=', today())
            ->whereDate('valid_until', '>=', today())
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->where('min_spend', '<=', $amount)
            ->first();

        if (! $promo) {
            return ['valid' => false, 'discount' => 0.0, 'promo' => null];
        }

        return [
            'valid' => true,
            'discount' => self::discountFor($promo->discount_type, (float) $promo->discount_value, $promo->max_discount === null ? null : (float) $promo->max_discount, $amount),
            'promo' => $promo,
        ];
    }

    // Used at checkout. A blank code is fine (no discount). A wrong code is reported as not valid.
    // Returns ['valid' => bool, 'discount' => float, 'reason' => ?string, 'promo' => ?Promo].
    public function adjustment(?string $code, float $subtotal): array
    {
        if (trim((string) $code) === '') {
            return ['valid' => true, 'discount' => 0.0, 'reason' => null, 'promo' => null];
        }

        $result = $this->validate($code, $subtotal);

        if (! $result['valid']) {
            return ['valid' => false, 'discount' => 0.0, 'reason' => null, 'promo' => null];
        }

        return [
            'valid' => true,
            'discount' => $result['discount'],
            'reason' => $result['promo']->promo_name ?: $code,
            'promo' => $result['promo'],
        ];
    }

    // Count one use. Refuses (returns false) if the usage limit was just reached by someone else.
    public function markUsed(Promo $promo): bool
    {
        return Promo::where('promoID', $promo->promoID)
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->increment('used_count') > 0;
    }

    // The discount maths on its own.
    public static function discountFor(string $type, float $value, ?float $maxDiscount, float $amount): float
    {
        if ($type === 'percentage') {
            $discount = ($amount * $value) / 100;

            if ($maxDiscount) {
                $discount = min($discount, $maxDiscount);
            }
        } else {
            $discount = min($value, $amount);
        }

        return round($discount, 2);
    }
}
