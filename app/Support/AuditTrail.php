<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditTrail
{
    /**
     * Insert audit log.
     * - UPDATE: stores only changed fields (old/new).
     * - INSERT: stores new_data.
     * - DELETE: stores old_data.
     */
    public static function insertLog(
        string $screenName,
        string $tableName,
        string $action,
        array|string|null $oldData = null,
        array|string|null $newData = null,
        string|int|null $tableId = null
    ): void {
        if (!in_array($action, ['INSERT', 'UPDATE', 'DELETE'], true)) {
            throw new \InvalidArgumentException('Audit:Invalid action type.');
        }

        $oldArr = self::normalizeData($oldData);
        $newArr = self::normalizeData($newData);

        [$changesOld, $changesNew] = self::diffByAction($action, $oldArr, $newArr);

        $isCli = app()->runningInConsole();
        $userId = $isCli ? null : Auth::id();
        $userLabel = $isCli ? 'SYSTEM' : null;

        DB::table('audit_trails')->insert([
            'user_id' => $userId,
            'user_label' => $userLabel,
            'screen_name' => $screenName,
            'table_name' => $tableName,
            'table_id' => $tableId,
            'action' => $action,
            'old_data' => $changesOld ? json_encode($changesOld) : null,
            'new_data' => $changesNew ? json_encode($changesNew) : null,
            'ip_address' => $isCli ? null : request()?->ip(),
            'user_agent' => $isCli ? null : request()?->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Helper: create audit from an Eloquent model event
     */
    public static function fromModel(Model $model, string $action, string $screenName = ''): void
    {
        $table = $model->getTable();
        $key = $model->getKey();

        if ($action === 'INSERT') {
            self::insertLog($screenName, $table, 'INSERT', null, $model->getAttributes(), $key);
            return;
        }

        if ($action === 'DELETE') {
            // on delete event, original is still available
            self::insertLog($screenName, $table, 'DELETE', $model->getOriginal(), null, $key);
            return;
        }

        if ($action === 'UPDATE') {
            $changes = $model->getChanges();
            if (empty($changes))
                return;

            $old = [];
            foreach (array_keys($changes) as $field) {
                $old[$field] = $model->getOriginal($field);
            }

            self::insertLog($screenName, $table, 'UPDATE', $old, $changes, $key);
            return;
        }
    }

    private static function normalizeData(array|string|null $data): array
    {
        if ($data === null || $data === '')
            return [];

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Audit:Invalid JSON format.');
            }
            // if it was an array of rows, take first row (matches your old behavior) :contentReference[oaicite:2]{index=2}
            return (is_array($decoded) && isset($decoded[0])) ? (array) $decoded[0] : (array) $decoded;
        }

        // if array of rows, take first row
        return (isset($data[0]) && is_array($data[0])) ? (array) $data[0] : $data;
    }

    private static function diffByAction(string $action, array $oldArr, array $newArr): array
    {
        if ($action === 'INSERT') {
            return [null, $newArr ?: null];
        }

        if ($action === 'DELETE') {
            return [$oldArr ?: null, null];
        }

        // UPDATE: only changed keys (like your old tool) :contentReference[oaicite:3]{index=3}
        $changesOld = [];
        $changesNew = [];

        foreach ($newArr as $key => $val) {
            $oldVal = $oldArr[$key] ?? null;
            if ($oldVal !== $val) {
                $changesOld[$key] = $oldVal;
                $changesNew[$key] = $val;
            }
        }

        return [$changesOld ?: null, $changesNew ?: null];
    }
}
