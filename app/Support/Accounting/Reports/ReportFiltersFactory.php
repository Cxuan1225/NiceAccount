<?php

namespace App\Support\Accounting\Reports;

use Illuminate\Http\Request;

final class ReportFiltersFactory {
    /**
     * Normalize status.
     * Input can be: posted/draft/void/all/''/null
     * Output:
     *  - statusRaw: original string (for UI)
     *  - status: '' means ALL, otherwise uppercase (POSTED/DRAFT/VOID)
     *  - statusLabel: "All" or "Posted"/"Draft"/"Void"
     */
    public static function status(Request $request, string $key = 'status', string $default = 'posted') : array {
        $statusRaw = (string) $request->query($key, $default);
        $rawTrim   = trim($statusRaw);

        $isAll = ($rawTrim === '' || strtolower($rawTrim) === 'all');

        $status      = $isAll ? '' : strtoupper($rawTrim);
        $statusLabel = $isAll ? 'All' : ucfirst(strtolower($status));

        return [
            'statusRaw'   => $statusRaw,
            'status'      => $status,
            'statusLabel' => $statusLabel,
        ];
    }

    /**
     * Read boolean flag from query, accepting both snake_case and camelCase.
     * E.g. show_zero=1 OR showZero=1
     */
    public static function boolean(Request $request, string $snakeKey, ?string $camelKey = null, bool $default = false) : bool {
        $camelKey = $camelKey ?? self::snakeToCamel($snakeKey);

        if ($request->query($snakeKey) !== null) {
            return (bool) $request->boolean($snakeKey, $default);
        }

        if ($camelKey && $request->query($camelKey) !== null) {
            return (bool) $request->boolean($camelKey, $default);
        }

        return $default;
    }

    /**
     * Date range filters used by P&L / TB / GL.
     * Returns UI-safe strings for from/to ('').
     */
    public static function dateRange(Request $request, array $opts = []) : array {
        $fromKey = $opts['fromKey'] ?? 'from';
        $toKey   = $opts['toKey'] ?? 'to';

        $from = $request->query($fromKey);
        $to   = $request->query($toKey);

        // UI-safe: '' instead of null
        $fromUi = ($from !== null && $from !== '') ? (string) $from : '';
        $toUi   = ($to !== null && $to !== '') ? (string) $to : '';

        return [
            'from'   => $fromUi,
            'to'     => $toUi,
            // raw values for queries (null means no filter)
            'fromDb' => $fromUi !== '' ? $fromUi : null,
            'toDb'   => $toUi !== '' ? $toUi : null,
        ];
    }

    /**
     * As-at filter used by Balance Sheet.
     * Returns UI-safe string for as_at (YYYY-MM-DD).
     */
    public static function asAt(Request $request, array $opts = []) : array {
        $key     = $opts['key'] ?? 'as_at';
        $default = $opts['default'] ?? now()->toDateString();

        $asAt   = $request->query($key);
        $asAtUi = ($asAt !== null && $asAt !== '') ? (string) $asAt : (string) $default;

        return [
            'as_at'  => $asAtUi,
            'asAtDb' => $asAtUi, // always present
        ];
    }

    /**
     * Account dropdown selection used by General Ledger.
     * Returns UI-safe string for <select>.
     */
    public static function accountId(Request $request, string $key = 'account_id') : array {
        $v = $request->query($key);

        if ($v === null || $v === '') {
            return [
                'account_id'  => '',
                'accountIdDb' => null,
            ];
        }

        // UI-safe string for select, DB int for queries
        return [
            'account_id'  => (string) $v,
            'accountIdDb' => (int) $v,
        ];
    }

    private static function snakeToCamel(string $snake) : string {
        $parts = explode('_', $snake);
        $first = array_shift($parts);

        $camel = $first;
        foreach ($parts as $p) {
            $camel .= ucfirst($p);
        }

        return $camel;
    }
}
