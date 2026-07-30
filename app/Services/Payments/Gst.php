<?php

namespace App\Services\Payments;

/**
 * GST on hotel accommodation (India). The applicable slab is decided by the
 * per-night tariff: up to the threshold it is taxed at the lower rate, above
 * it at the higher rate. Rates are configured in config/services.php so a
 * council revision is a config change, not a code change.
 */
class Gst
{
    /**
     * @return array{rate_bp: int, tax_minor: int, cgst_minor: int, sgst_minor: int}
     */
    public static function forStay(int $tariffMinor, int $nights): array
    {
        $perNight = $nights > 0 ? intdiv($tariffMinor, $nights) : $tariffMinor;

        $rateBp = $perNight <= (int) config('services.gst.threshold_minor')
            ? (int) config('services.gst.low_rate_bp')
            : (int) config('services.gst.high_rate_bp');

        $tax = (int) round($tariffMinor * $rateBp / 10000);
        $cgst = intdiv($tax, 2);

        return [
            'rate_bp' => $rateBp,
            'tax_minor' => $tax,
            'cgst_minor' => $cgst,
            'sgst_minor' => $tax - $cgst,
        ];
    }

    public static function ratePercent(int $rateBp): string
    {
        return rtrim(rtrim(number_format($rateBp / 100, 2), '0'), '.');
    }
}
