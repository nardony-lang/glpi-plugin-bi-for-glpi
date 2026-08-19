<?php

namespace GlpiPlugin\Biforglpi;

use InvalidArgumentException;
use RuntimeException;
use Session;

final class SavedQuery
{
    public const TABLE = 'glpi_plugin_biforglpi_savedqueries';
    public const TYPE_NUMBER = 'number';
    public const TYPE_TABLE = 'table';

    /** @return list<array<string, mixed>> */
    public static function all(bool $activeOnly = false): array
    {
        global $DB;

        $criteria = [
            'FROM'  => self::TABLE,
            'ORDER' => ['name ASC'],
        ];
        if ($activeOnly) {
            $criteria['WHERE'] = ['is_active' => 1];
        }

        $rows = [];
        foreach ($DB->request($criteria) as $row) {
            $rows[] = self::castRow($row);
        }
        return $rows;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        global $DB;

        foreach ($DB->request([
            'FROM'  => self::TABLE,
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

        Session::checkRight(Profile::RIGHT_MANAGE_QUERIES, UPDATE);
        $data = self::validate($input);
        $now = date('Y-m-d H:i:s');
        $data['users_id'] = (int) Session::getLoginUserID();
        $data['date_creation'] = $now;
        $data['date_mod'] = $now;

        if (!$DB->insert(self::TABLE, $data)) {
            throw new RuntimeException('Não foi possível salvar a consulta.');
        }
        return (int) $DB->insertId();
    }

    /** @param array<string, mixed> $input */
    public static function update(int $id, array $input): void
    {
        global $DB;

        Session::checkRight(Profile::RIGHT_MANAGE_QUERIES, UPDATE);
        if (self::find($id) === null) {
            throw new InvalidArgumentException('Consulta salva não encontrada.');
        }

        $data = self::validate($input);
        $data['date_mod'] = date('Y-m-d H:i:s');
        if (!$DB->update(self::TABLE, $data, ['id' => $id])) {
            throw new RuntimeException('Não foi possível atualizar a consulta.');
        }
    }

    public static function delete(int $id): void
    {
        global $DB;

        Session::checkRight(Profile::RIGHT_MANAGE_QUERIES, UPDATE);
        foreach ($DB->request([
            'FROM' => DashboardWidget::TABLE,
            'WHERE' => ['savedqueries_id' => $id],
            'LIMIT' => 1,
        ]) as $widget) {
            throw new InvalidArgumentException('Remova esta consulta dos dashboards antes de excluí-la.');
        }
        if (!$DB->delete(self::TABLE, ['id' => $id])) {
            throw new RuntimeException('Não foi possível excluir a consulta.');
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array{name: string, description: string|null, query_sql: string, visualization: string, row_limit: int, is_active: int}
     */
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

        $querySql = (new SqlReadOnlyGuard())->validate((string) ($input['query_sql'] ?? ''));
        $visualization = (string) ($input['visualization'] ?? self::TYPE_NUMBER);
        if (!in_array($visualization, [self::TYPE_NUMBER, self::TYPE_TABLE], true)) {
            throw new InvalidArgumentException('Tipo de visualização inválido.');
        }

        $rowLimit = filter_var($input['row_limit'] ?? SqlExecutor::DEFAULT_LIMIT, FILTER_VALIDATE_INT);
        if ($rowLimit === false || $rowLimit < 1 || $rowLimit > SqlExecutor::MAX_LIMIT) {
            throw new InvalidArgumentException('O limite deve estar entre 1 e 500 linhas.');
        }

        return [
            'name'          => $name,
            'description'   => $description !== '' ? $description : null,
            'query_sql'     => $querySql,
            'visualization' => $visualization,
            'row_limit'     => (int) $rowLimit,
            'is_active'     => !empty($input['is_active']) ? 1 : 0,
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private static function castRow(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['row_limit'] = (int) $row['row_limit'];
        $row['is_active'] = (int) $row['is_active'];
        $row['users_id'] = (int) $row['users_id'];
        return $row;
    }
}
