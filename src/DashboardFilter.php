<?php

namespace GlpiPlugin\Biforglpi;

use DateTimeImmutable;
use InvalidArgumentException;

final class DashboardFilter
{
    /** @param array<string,mixed> $input @return array{entity_id:int,date_start:string,date_end:string} */
    public static function context(array $input): array
    {
        $today = new DateTimeImmutable('today');
        $start = (string) ($input['date_start'] ?? $today->format('Y-m-01'));
        $end = (string) ($input['date_end'] ?? $today->format('Y-m-t'));
        $startDate = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
        $endDate = DateTimeImmutable::createFromFormat('!Y-m-d', $end);
        if ($startDate === false || $endDate === false || $startDate->format('Y-m-d') !== $start || $endDate->format('Y-m-d') !== $end) {
            throw new InvalidArgumentException('Período inválido.');
        }
        if ($startDate > $endDate || $startDate->diff($endDate)->days > 366) {
            throw new InvalidArgumentException('O período deve ter no máximo 367 dias.');
        }

        $allowed = self::allowedEntityIds();
        $defaultEntity = (int) ($_SESSION['glpiactive_entity'] ?? ($allowed[0] ?? 0));
        $entityId = filter_var($input['entity_id'] ?? $defaultEntity, FILTER_VALIDATE_INT);
        if ($entityId === false || !in_array((int) $entityId, $allowed, true)) {
            $entityId = $defaultEntity;
        }
        return ['entity_id' => (int) $entityId, 'date_start' => $start, 'date_end' => $end];
    }

    /** @return array<int,string> */
    public static function entities(): array
    {
        global $DB;
        $ids = self::allowedEntityIds();
        if ($ids === []) {
            return [];
        }
        $names = [];
        foreach ($DB->request(['FROM' => 'glpi_entities', 'WHERE' => ['id' => $ids], 'ORDER' => ['completename ASC']]) as $row) {
            $names[(int) $row['id']] = (string) ($row['completename'] ?: $row['name']);
        }
        return $names;
    }

    /** @return list<int> */
    private static function allowedEntityIds(): array
    {
        $ids = array_map('intval', (array) ($_SESSION['glpiactiveentities'] ?? []));
        $active = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        if (isset($_SESSION['glpiactive_entity'])) {
            $ids[] = $active;
        }
        return array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id >= 0)));
    }
}
