<?php

use GlpiPlugin\Biforglpi\SqlQueryTimeout;
use GlpiPlugin\Biforglpi\SqlReadOnlyGuard;
use GlpiPlugin\Biforglpi\SavedQuery;

require_once __DIR__ . '/../src/SqlQueryTimeout.php';
require_once __DIR__ . '/../src/SqlReadOnlyGuard.php';
require_once __DIR__ . '/../src/SqlExecutor.php';
require_once __DIR__ . '/../src/SavedQuery.php';

function assertSameValue(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            "%s falhou.\nEsperado: %s\nRecebido: %s",
            $label,
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
}

assertSameValue(
    'CSS no diretório público do GLPI 11',
    true,
    is_file(__DIR__ . '/../public/css/sqllab.css')
);
assertSameValue(
    'JavaScript no diretório público do GLPI 11',
    true,
    is_file(__DIR__ . '/../public/js/sqllab.js')
);
require_once __DIR__ . '/asset_hooks.php';

$savedQuery = SavedQuery::validate([
    'name'          => 'Chamados em atendimento',
    'description'   => 'Total de chamados atribuídos ou planejados',
    'query_sql'     => 'SELECT COUNT(*) AS total FROM glpi_tickets WHERE status IN (2, 3)',
    'visualization' => SavedQuery::TYPE_NUMBER,
    'row_limit'     => 1,
    'is_active'     => 1,
]);
assertSameValue('Nome da consulta salva', 'Chamados em atendimento', $savedQuery['name']);
assertSameValue('Tipo de indicador salvo', SavedQuery::TYPE_NUMBER, $savedQuery['visualization']);
assertSameValue('Consulta salva ativa', 1, $savedQuery['is_active']);

try {
    SavedQuery::validate([
        'name'          => 'Consulta perigosa',
        'query_sql'     => 'DELETE FROM glpi_tickets',
        'visualization' => SavedQuery::TYPE_TABLE,
        'row_limit'     => 100,
    ]);
    throw new RuntimeException('Uma consulta salva perigosa foi aceita.');
} catch (InvalidArgumentException) {
    // Expected.
}

$timeout = new SqlQueryTimeout(10);
assertSameValue(
    'Timeout MySQL',
    'SELECT /*+ MAX_EXECUTION_TIME(10000) */ * FROM glpi_computers',
    $timeout->apply('SELECT * FROM glpi_computers', '8.0.42')
);
assertSameValue(
    'Timeout MariaDB',
    "SET STATEMENT max_statement_time=10 FOR\nSELECT * FROM glpi_computers",
    $timeout->apply('SELECT * FROM glpi_computers', '10.11.13-MariaDB')
);
assertSameValue(
    'Timeout EXPLAIN WITH no MySQL',
    'EXPLAIN WITH recent AS (SELECT /*+ MAX_EXECUTION_TIME(10000) */ id FROM glpi_computers) SELECT * FROM recent',
    $timeout->apply(
        'EXPLAIN WITH recent AS (SELECT id FROM glpi_computers) SELECT * FROM recent',
        '8.0.42'
    )
);

$guard = new SqlReadOnlyGuard();
foreach (['SELECT 1', 'WITH data AS (SELECT 1) SELECT * FROM data', 'EXPLAIN SELECT 1'] as $sql) {
    assertSameValue('Consulta permitida', $sql, $guard->validate($sql));
}

foreach (['DELETE FROM glpi_computers', 'SELECT SLEEP(1)', 'SELECT 1; SELECT 2'] as $sql) {
    try {
        $guard->validate($sql);
        throw new RuntimeException('Consulta perigosa foi aceita: ' . $sql);
    } catch (InvalidArgumentException) {
        // Expected.
    }
}

echo "Todos os testes passaram.\n";
