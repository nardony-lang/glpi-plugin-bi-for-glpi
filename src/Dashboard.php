<?php

namespace GlpiPlugin\Biforglpi;

use InvalidArgumentException;
use RuntimeException;
use Session;

final class Dashboard
{
    public const TABLE = 'glpi_plugin_biforglpi_dashboards';

    /** @return list<array<string, mixed>> */
    public static function accessible(): array
    {
        global $DB;

        $rows = [];
        foreach ($DB->request(['FROM' => self::TABLE, 'ORDER' => ['name ASC']]) as $row) {
            $dashboard = self::castRow($row);
            if (DashboardAccess::canView((int) $dashboard['id'])) {
                $rows[] = $dashboard;
            }
        }
        return $rows;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        global $DB;

        foreach ($DB->request([
            'FROM' => self::TABLE,
            'WHERE' => ['id' => $id],
            'LIMIT' => 1,
        ]) as $row) {
            return self::castRow($row);
        }
        return null;
    }

    /** @param array<string, mixed> $input */
    public static function create(array $input): int
    {
        global $DB;

        Session::checkRight(Profile::RIGHT_MANAGE_DASHBOARDS, UPDATE);
        $data = self::validate($input);
        $now = date('Y-m-d H:i:s');
        $data['users_id'] = (int) Session::getLoginUserID();
        $data['date_creation'] = $now;
        $data['date_mod'] = $now;

        if (!$DB->insert(self::TABLE, $data)) {
            throw new RuntimeException('Não foi possível salvar o dashboard.');
        }

        $id = (int) $DB->insertId();
        DashboardAccess::grantInstallerProfile($id);
        return $id;
    }

    /** @param array<string, mixed> $input */
    public static function update(int $id, array $input): void
    {
        global $DB;

        DashboardAccess::checkEdit($id);
        if (self::find($id) === null) {
            throw new InvalidArgumentException('Dashboard não encontrado.');
        }
        $data = self::validate($input);
        $data['date_mod'] = date('Y-m-d H:i:s');
        if (!$DB->update(self::TABLE, $data, ['id' => $id])) {
            throw new RuntimeException('Não foi possível atualizar o dashboard.');
        }
    }

    public static function delete(int $id): void
    {
        global $DB;

        DashboardAccess::checkEdit($id);
        $DB->delete(DashboardWidget::TABLE, ['dashboards_id' => $id]);
        $DB->delete(DashboardAccess::TABLE, ['dashboards_id' => $id]);
        if (!$DB->delete(self::TABLE, ['id' => $id])) {
            throw new RuntimeException('Não foi possível excluir o dashboard.');
        }
    }

    /** @param array<string, mixed> $input @return array{name:string,description:?string,is_active:int,is_demo:int} */
    public static function validate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '' || strlen($name) > 255) {
            throw new InvalidArgumentException('Informe um nome com até 255 caracteres.');
        }
        $description = trim((string) ($input['description'] ?? ''));
        if (strlen($description) > 2_000) {
            throw new InvalidArgumentException('A descrição deve ter até 2.000 caracteres.');
        }
        return [
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'is_demo' => !empty($input['is_demo']) ? 1 : 0,
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private static function castRow(array $row): array
    {
        foreach (['id', 'is_active', 'is_demo', 'users_id'] as $field) {
            $row[$field] = (int) $row[$field];
        }
        return $row;
    }
}
