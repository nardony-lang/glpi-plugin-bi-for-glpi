<?php

use GlpiPlugin\Biforglpi\SqlQueryTimeout;
use GlpiPlugin\Biforglpi\SqlReadOnlyGuard;
use GlpiPlugin\Biforglpi\SavedQuery;
use GlpiPlugin\Biforglpi\Dashboard;
use GlpiPlugin\Biforglpi\DashboardWidget;
use GlpiPlugin\Biforglpi\DashboardFilter;
use GlpiPlugin\Biforglpi\IndicatorCatalog;
use GlpiPlugin\Biforglpi\SqlTemplate;

require_once __DIR__ . '/../src/SqlQueryTimeout.php';
require_once __DIR__ . '/../src/SqlReadOnlyGuard.php';
require_once __DIR__ . '/../src/SqlTemplate.php';
require_once __DIR__ . '/../src/SqlExecutor.php';
require_once __DIR__ . '/../src/SavedQuery.php';
require_once __DIR__ . '/../src/Dashboard.php';
require_once __DIR__ . '/../src/DashboardAccess.php';
require_once __DIR__ . '/../src/DashboardWidget.php';
require_once __DIR__ . '/../src/DashboardFilter.php';
require_once __DIR__ . '/../src/IndicatorCatalog.php';

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
assertSameValue('JavaScript de gráficos local', true, is_file(__DIR__ . '/../public/js/dashboard.js'));
assertSameValue('JavaScript de configuração do Gauge', true, is_file(__DIR__ . '/../public/js/widget.js'));
assertSameValue('Logo próprio do plugin', true, is_file(__DIR__ . '/../logo.png'));
$logoInfo = getimagesize(__DIR__ . '/../logo.png');
assertSameValue(
    'Logo PNG quadrado e otimizado',
    [256, 256, 'image/png'],
    is_array($logoInfo) ? [$logoInfo[0], $logoInfo[1], $logoInfo['mime']] : null
);

foreach (['dashboard.form.php', 'widget.form.php', 'dashboardrights.form.php'] as $formFile) {
    $formSource = file_get_contents(__DIR__ . '/../front/' . $formFile);
    assertSameValue(
        'Redirecionamento fora do tratamento de erro em ' . $formFile,
        true,
        is_string($formSource)
            && strrpos($formSource, 'Html::redirect') > strrpos($formSource, 'catch (Throwable')
    );
}

$rightsFormSource = file_get_contents(__DIR__ . '/../front/dashboardrights.form.php');
assertSameValue(
    'Banco importado no escopo da página de permissões do GLPI 11',
    true,
    is_string($rightsFormSource)
        && strpos($rightsFormSource, 'global $DB;') < strpos($rightsFormSource, '$DB->request')
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

$template = new SqlTemplate();
$templatedSql = 'SELECT * FROM glpi_tickets WHERE entities_id = {{entity_id}} AND solvedate >= {{date_start}} AND solvedate < {{date_end_exclusive}}';
assertSameValue('Variáveis preservadas ao salvar', $templatedSql, $template->validate($templatedSql));
assertSameValue(
    'Variáveis compiladas com tipos seguros',
    "SELECT * FROM glpi_tickets WHERE entities_id = 2 AND solvedate >= '2026-08-01 00:00:00' AND solvedate < '2026-09-01 00:00:00'",
    $template->compile($templatedSql, ['entity_id' => 2, 'date_start' => '2026-08-01', 'date_end' => '2026-08-31'])
);
foreach ([
    'SELECT {{unknown_token}}',
    'SELECT * FROM glpi_tickets WHERE date >= {{date_start',
] as $invalidTemplate) {
    try {
        $template->validate($invalidTemplate);
        throw new RuntimeException('Variável SQL inválida foi aceita.');
    } catch (InvalidArgumentException) {
        // Expected.
    }
}

$_SESSION['glpiactive_entity'] = 2;
$_SESSION['glpiactiveentities'] = [2, 3];
$filterContext = DashboardFilter::context(['entity_id' => '3', 'date_start' => '2026-08-01', 'date_end' => '2026-08-31']);
assertSameValue('Filtro aceita entidade autorizada', 3, $filterContext['entity_id']);
$fallbackContext = DashboardFilter::context(['entity_id' => '999']);
assertSameValue('Filtro rejeita entidade não autorizada', 2, $fallbackContext['entity_id']);

$catalog = IndicatorCatalog::all();
assertSameValue('Catálogo inicial com cinco indicadores', 5, count($catalog));
foreach ($catalog as $catalogItem) {
    assertSameValue(
        'Consulta do catálogo preserva modelo seguro',
        $catalogItem['query_sql'],
        $template->validate((string) $catalogItem['query_sql'])
    );
}

$dashboard = Dashboard::validate([
    'name' => 'Gestão de Requisições',
    'description' => 'Indicadores mensais',
    'is_active' => 1,
    'is_demo' => 1,
    'use_entity_filter' => 1,
    'use_period_filter' => 1,
]);
assertSameValue('Dashboard em demonstração', 1, $dashboard['is_demo']);
assertSameValue('Dashboard com filtro de entidade', 1, $dashboard['use_entity_filter']);
assertSameValue('Dashboard com filtro de período', 1, $dashboard['use_period_filter']);

$widget = DashboardWidget::validate([
    'savedqueries_id' => 1,
    'title' => 'Requisições por grupo',
    'widget_type' => 'bar',
    'position' => 2,
    'width' => 6,
    'demo_data' => '[{"grupo":"Service Desk","total":15}]',
]);
assertSameValue('Componente gráfico', 'bar', $widget['widget_type']);
assertSameValue('Largura do componente', 6, $widget['width']);

$gaugeWidget = DashboardWidget::validate([
    'savedqueries_id' => 1,
    'widget_type' => 'gauge',
    'width' => 4,
    'gauge_min' => 0,
    'gauge_max' => 100,
    'gauge_target' => 95,
    'gauge_warning' => 80,
    'gauge_success' => 95,
    'gauge_unit' => '%',
    'gauge_color_low' => '#d63939',
    'gauge_color_mid' => '#f59f00',
    'gauge_color_high' => '#2fb344',
]);
$gaugeSettings = json_decode((string) $gaugeWidget['settings_json'], true);
assertSameValue('Componente Gauge', 'gauge', $gaugeWidget['widget_type']);
assertSameValue('Meta do Gauge', 95.0, isset($gaugeSettings['target']) ? (float) $gaugeSettings['target'] : null);
assertSameValue('Unidade do Gauge', '%', $gaugeSettings['unit'] ?? null);

try {
    DashboardWidget::validate([
        'savedqueries_id' => 1,
        'widget_type' => 'gauge',
        'gauge_min' => 100,
        'gauge_max' => 0,
        'gauge_warning' => 80,
        'gauge_success' => 95,
    ]);
    throw new RuntimeException('Gauge com intervalo inválido foi aceito.');
} catch (InvalidArgumentException) {
    // Expected.
}

assertSameValue('Indicador de ANS usa Gauge', 'gauge', $catalog['within_sla']['widget_type'] ?? null);

try {
    DashboardWidget::validate([
        'savedqueries_id' => 1,
        'widget_type' => 'bar',
        'width' => 5,
        'demo_data' => 'inválido',
    ]);
    throw new RuntimeException('Dados de demonstração inválidos foram aceitos.');
} catch (InvalidArgumentException) {
    // Expected.
}

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
