<?php

namespace GlpiPlugin\Biforglpi;

use DBConnection;
use RuntimeException;
use Throwable;

final class SqlExecutor
{
    public const DEFAULT_LIMIT = 100;
    public const MAX_LIMIT = 500;
    public const MAX_EXECUTION_TIME_SECONDS = 10;

    /**
     * @return array{columns: list<string>, rows: list<array<string, mixed>>, elapsed_ms: float, truncated: bool, limit: int}
     */
    public function execute(string $sql, int $limit): array
    {
        $limit = max(1, min(self::MAX_LIMIT, $limit));
        $isExplain = preg_match('/^\s*EXPLAIN\b/i', $sql) === 1;
        $query = $isExplain
            ? $sql
            : sprintf(
                "SELECT * FROM (\n%s\n) AS `_biforglpi_result` LIMIT %d",
                $sql,
                $limit + 1
            );

        $connection = DBConnection::getReadConnection();
        $versionResult = $connection->doQuery('SELECT VERSION() AS `version`');
        $versionRow = is_object($versionResult) && method_exists($versionResult, 'fetch_assoc')
            ? $versionResult->fetch_assoc()
            : null;
        if (!is_array($versionRow) || !isset($versionRow['version'])) {
            throw new RuntimeException('Não foi possível identificar a versão do banco de dados.');
        }

        $query = (new SqlQueryTimeout(self::MAX_EXECUTION_TIME_SECONDS))->apply(
            $query,
            (string) $versionRow['version']
        );

        $transactionStarted = false;
        $startedAt = hrtime(true);
        try {
            $transactionStarted = $connection->doQuery('START TRANSACTION READ ONLY') === true;
            if (!$transactionStarted) {
                throw new RuntimeException('Não foi possível iniciar uma transação somente leitura.');
            }

            $result = $connection->doQuery($query);
            $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;

            if (!is_object($result) || !method_exists($result, 'fetch_assoc')) {
                throw new RuntimeException('A consulta não retornou um conjunto de resultados.');
            }

            $columns = [];
            if (method_exists($result, 'fetch_fields')) {
                foreach ($result->fetch_fields() as $field) {
                    $columns[] = (string) $field->name;
                }
            }

            $rows = [];
            $truncated = false;
            while (($row = $result->fetch_assoc()) !== null) {
                if ($columns === []) {
                    $columns = array_map('strval', array_keys($row));
                }

                if (count($rows) >= $limit) {
                    $truncated = true;
                    break;
                }

                $rows[] = $row;
            }
        } catch (Throwable $exception) {
            throw new RuntimeException('O banco recusou a consulta: ' . $exception->getMessage(), 0, $exception);
        } finally {
            if ($transactionStarted) {
                $connection->doQuery('ROLLBACK');
            }
        }

        return [
            'columns'    => $columns,
            'rows'       => $rows,
            'elapsed_ms' => round($elapsedMs, 2),
            'truncated'  => $truncated,
            'limit'      => $limit,
        ];
    }
}
