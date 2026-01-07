<?php

namespace App\Support\Accounting;

final class NormalBalance {
    /**
     * Returns signed amount based on account type.
     * Debit-normal: ASSET, EXPENSE  => debit - credit
     * Credit-normal: LIABILITY, EQUITY, INCOME => credit - debit
     */
    public static function amount(string $type, float $debit, float $credit) : float {
        $type = strtoupper(trim($type));

        if ($type === 'ASSET' || $type === 'EXPENSE') {
            return $debit - $credit;
        }

        return $credit - $debit;
    }

    /** Convenience: returns 'DEBIT' or 'CREDIT' normal side for UI */
    public static function side(string $type) : string {
        $type = strtoupper(trim($type));
        return ($type === 'ASSET' || $type === 'EXPENSE') ? 'DEBIT' : 'CREDIT';
    }
}
