<?php

namespace GlpiPlugin\Biforglpi;

use RuntimeException;

final class SqlQueryTimeout
{
    public function __construct(private readonly int $seconds)
    {
        if ($seconds < 1) {
            throw new RuntimeException('O tempo máximo da consulta deve ser positivo.');
        }
    }

    public function apply(string $query, string $serverVersion): string
    {
        if (stripos($serverVersion, 'MariaDB') !== false) {
            return sprintf(
                "SET STATEMENT max_statement_time=%d FOR\n%s",
                $this->seconds,
                $query
            );
        }

        $replacementCount = 0;
        $timedQuery = preg_replace(
            '/\bSELECT\b/i',
            sprintf('SELECT /*+ MAX_EXECUTION_TIME(%d) */', $this->seconds * 1_000),
            $query,
            1,
            $replacementCount
        );

        if (!is_string($timedQuery) || $replacementCount !== 1) {
            throw new RuntimeException('Não foi possível aplicar o tempo máximo à consulta.');
        }

        return $timedQuery;
    }
}
