<?php

namespace GlpiPlugin\Biforglpi;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class DashboardWidget
{
    public const TABLE = 'glpi_plugin_biforglpi_dashboardwidgets';
    public const TYPES = ['number', 'table', 'bar', 'line', 'doughnut'];

    /** @return list<array<string, mixed>> */
    public static function allForDashboard(int $dashboardId): array
    {
        global $DB;
        $rows = [];
        foreach ($DB->request([
            'FROM' => self::TABLE,
            'WHERE' => ['dashboards_id' => $dashboardId],
            'ORDER' => ['position ASC', 'id ASC'],
        ]) as $row) {
            $rows[] = self::castRow($row);
        }
        return $rows;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        global $DB;
        foreach ($DB->request(['FROM' => self::TABLE, 'WHERE' => ['id' => $id], 'LIMIT' => 1]) as $row) {
            return self::castRow($row);
        }
        return null;
    }

    /** @param array<string, mixed> $input */
    public static function create(int $dashboardId, array $input): int
    {
        global $DB;
        DashboardAccess::checkEdit($dashboardId);
        $data = self::validate($input) + ['dashboards_id' => $dashboardId];
        $now = date('Y-m-d H:i:s');
        $data['date_creation'] = $now;
        $data['date_mod'] = $now;
        if (!$DB->insert(self::TABLE, $data)) {
            throw new RuntimeException('Não foi possível salvar o componente.');
        }
        return (int) $DB->insertId();
    }

    /** @param array<string, mixed> $input */
    public static function update(int $id, array $input): void
    {
        global $DB;
        $widget = self::find($id);
        if ($widget === null) {
            throw new InvalidArgumentException('Componente não encontrado.');
        }
        DashboardAccess::checkEdit((int) $widget['dashboards_id']);
        $data = self::validate($input);
        $data['date_mod'] = date('Y-m-d H:i:s');
        if (!$DB->update(self::TABLE, $data, ['id' => $id])) {
            throw new RuntimeException('Não foi possível atualizar o componente.');
        }
    }

    public static function delete(int $id): void
    {
        global $DB;
        $widget = self::find($id);
        if ($widget === null) {
            return;
        }
        DashboardAccess::checkEdit((int) $widget['dashboards_id']);
        if (!$DB->delete(self::TABLE, ['id' => $id])) {
            throw new RuntimeException('Não foi possível excluir o componente.');
        }
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public static function validate(array $input): array
    {
        $savedQueryId = filter_var($input['savedqueries_id'] ?? null, FILTER_VALIDATE_INT);
        if ($savedQueryId === false || $savedQueryId < 1) {
            throw new InvalidArgumentException('Selecione uma consulta salva.');
        }
        $title = trim((string) ($input['title'] ?? ''));
        if (strlen($title) > 255) {
            throw new InvalidArgumentException('O título deve ter até 255 caracteres.');
        }
        $type = (string) ($input['widget_type'] ?? 'number');
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Tipo de componente inválido.');
        }
        $width = filter_var($input['width'] ?? 4, FILTER_VALIDATE_INT);
        if ($width === false || !in_array($width, [3, 4, 6, 8, 12], true)) {
            throw new InvalidArgumentException('Largura de componente inválida.');
        }
        $position = filter_var($input['position'] ?? 0, FILTER_VALIDATE_INT);
        if ($position === false || $position < 0 || $position > 999) {
            throw new InvalidArgumentException('A posição deve estar entre 0 e 999.');
        }
        $demoData = trim((string) ($input['demo_data'] ?? ''));
        if ($demoData !== '') {
            try {
                $decoded = json_decode($demoData, true, 32, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('Os dados de demonstração devem estar em JSON válido.', 0, $exception);
            }
            if (!is_array($decoded) || !array_is_list($decoded)) {
                throw new InvalidArgumentException('Os dados de demonstração devem ser uma lista JSON.');
            }
            $demoData = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
        return [
            'savedqueries_id' => (int) $savedQueryId,
            'title' => $title !== '' ? $title : null,
            'widget_type' => $type,
            'position' => (int) $position,
            'width' => (int) $width,
            'demo_data' => $demoData !== '' ? $demoData : null,
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private static function castRow(array $row): array
    {
        foreach (['id', 'dashboards_id', 'savedqueries_id', 'position', 'width'] as $field) {
            $row[$field] = (int) $row[$field];
        }
        return $row;
    }
}
