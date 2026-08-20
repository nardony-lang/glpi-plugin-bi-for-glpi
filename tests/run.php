<?php

use GlpiPlugin\Biforglpi\SqlQueryTimeout;
use GlpiPlugin\Biforglpi\SqlReadOnlyGuard;
use GlpiPlugin\Biforglpi\SavedQuery;
use GlpiPlugin\Biforglpi\Dashboard;
use GlpiPlugin\Biforglpi\DashboardWidget;
use GlpiPlugin\Biforglpi\DashboardFilter;
use GlpiPlugin\Biforglpi\IndicatorCatalog;
use GlpiPlugin\Biforglpi\SqlTemplate;
use GlpiPlugin\Biforglpi\WidgetRenderer;

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
require_once __DIR__ . '/../src/WidgetRenderer.php';

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
assertSameValue('Fallback Canvas local', true, is_file(__DIR__ . '/../public/js/dashboard-canvas.js'));
assertSameValue('Apache ECharts empacotado localmente', true, is_file(__DIR__ . '/../public/vendor/echarts/echarts.min.js'));
assertSameValue(
    'Integridade do Apache ECharts 6.1.0',
    'b66b25aeb4df84e33199dc21694014d336d222cbd9deb0e5a7c14bd6aa0d0fd0',
    hash_file('sha256', __DIR__ . '/../public/vendor/echarts/echarts.min.js')
);
assertSameValue('Licença do Apache ECharts incluída', true, is_file(__DIR__ . '/../public/vendor/echarts/LICENSE.txt'));
assertSameValue('JavaScript de tabelas analíticas', true, is_file(__DIR__ . '/../public/js/table.js'));
assertSameValue('html2canvas empacotado localmente', true, is_file(__DIR__ . '/../public/vendor/html2canvas/html2canvas.min.js'));
assertSameValue(
    'Integridade do html2canvas 1.4.1',
    'e87e550794322e574a1fda0c1549a3c70dae5a93d9113417a429016838eab8cb',
    hash_file('sha256', __DIR__ . '/../public/vendor/html2canvas/html2canvas.min.js')
);
assertSameValue('Licença do html2canvas incluída', true, is_file(__DIR__ . '/../public/vendor/html2canvas/LICENSE.txt'));
assertSameValue('jsPDF empacotado localmente', true, is_file(__DIR__ . '/../public/vendor/jspdf/jspdf.umd.min.js'));
assertSameValue(
    'Integridade do jsPDF 4.2.1',
    'e6551fcdc32f09d6853b2c5126d18d01d9447e0da618a41a11ebeee0f6c20d54',
    hash_file('sha256', __DIR__ . '/../public/vendor/jspdf/jspdf.umd.min.js')
);
assertSameValue('Licença do jsPDF incluída', true, is_file(__DIR__ . '/../public/vendor/jspdf/LICENSE.txt'));
assertSameValue('JavaScript de configuração do Gauge', true, is_file(__DIR__ . '/../public/js/widget.js'));
assertSameValue('JavaScript do editor visual', true, is_file(__DIR__ . '/../public/js/builder.js'));
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
    'bar_orientation' => 'horizontal',
    'bar_color' => '#206bc4',
    'bar_use_palette' => 1,
    'bar_show_values' => 1,
    'bar_show_grid' => 1,
    'bar_decimals' => 0,
    'bar_unit' => '',
]);
$barSettings = json_decode((string) $widget['settings_json'], true);
assertSameValue('Componente gráfico', 'bar', $widget['widget_type']);
assertSameValue('Largura do componente', 6, $widget['width']);
assertSameValue('Orientação das barras', 'horizontal', $barSettings['orientation'] ?? null);
assertSameValue('Paleta das barras', 1, $barSettings['use_palette'] ?? null);
$barRender = (new WidgetRenderer())->prepare(
    ['widget_type' => 'bar', 'demo_data' => $widget['demo_data'], 'settings' => $barSettings],
    ['query_sql' => 'SELECT grupo, total', 'row_limit' => 10],
    true,
    ['entity_id' => 2, 'date_start' => '2026-08-01', 'date_end' => '2026-08-31']
);
assertSameValue('Configuração de barras no renderizador', 'horizontal', $barRender['chart']['settings']['orientation'] ?? null);

$lineWidget = DashboardWidget::validate([
    'savedqueries_id' => 1,
    'widget_type' => 'line',
    'line_color' => '#6f42c1',
    'line_show_values' => 1,
    'line_show_grid' => 1,
    'line_show_points' => 1,
    'line_fill_area' => 1,
    'line_smooth' => 1,
    'line_decimals' => 1,
    'line_unit' => '%',
]);
$lineSettings = json_decode((string) $lineWidget['settings_json'], true);
assertSameValue('Cor do gráfico de linha', '#6f42c1', $lineSettings['color'] ?? null);
assertSameValue('Área preenchida da linha', 1, $lineSettings['fill_area'] ?? null);
assertSameValue('Unidade da linha', '%', $lineSettings['unit'] ?? null);

$doughnutWidget = DashboardWidget::validate([
    'savedqueries_id' => 1,
    'widget_type' => 'doughnut',
    'doughnut_legend_position' => 'bottom',
    'doughnut_hole_size' => 60,
    'doughnut_show_labels' => 1,
    'doughnut_show_percentages' => 1,
    'doughnut_decimals' => 1,
    'doughnut_unit' => ' h',
]);
$doughnutSettings = json_decode((string) $doughnutWidget['settings_json'], true);
assertSameValue('Posição da legenda da rosca', 'bottom', $doughnutSettings['legend_position'] ?? null);
assertSameValue('Tamanho do centro da rosca', 60, $doughnutSettings['hole_size'] ?? null);
assertSameValue('Percentuais da rosca', 1, $doughnutSettings['show_percentages'] ?? null);
$doughnutRender = (new WidgetRenderer())->prepare(
    ['widget_type' => 'doughnut', 'demo_data' => '[{"grupo":"N1","total":12},{"grupo":"N2","total":8}]', 'settings' => $doughnutSettings],
    ['query_sql' => 'SELECT grupo, total', 'row_limit' => 10],
    true,
    ['entity_id' => 2, 'date_start' => '2026-08-01', 'date_end' => '2026-08-31']
);
assertSameValue('Configuração de rosca no renderizador', 'bottom', $doughnutRender['chart']['settings']['legend_position'] ?? null);

$tableWidget = DashboardWidget::validate([
    'savedqueries_id' => 1,
    'widget_type' => 'table',
    'table_striped' => 1,
    'table_compact' => 1,
    'table_sticky_header' => 1,
    'table_show_unconfigured' => 0,
    'table_export_png' => 1,
    'table_export_pdf' => 1,
    'table_column_source' => ['indicador', 'resultado', 'historico', 'tempo'],
    'table_column_label' => ['Indicador', 'Resultado', 'Tendência', 'Tempo médio'],
    'table_column_type' => ['text', 'progress', 'sparkline_line', 'duration'],
    'table_column_decimals' => [-1, 1, 1, -1],
    'table_column_prefix' => ['', '', '', ''],
    'table_column_suffix' => ['', '%', '', ''],
    'table_column_width' => [240, 180, 180, 120],
    'table_column_align' => ['left', 'right', 'center', 'right'],
    'table_column_color' => ['#206bc4', '#2fb344', '#6f42c1', '#206bc4'],
    'table_column_min' => ['', 0, '', ''],
    'table_column_max' => ['', 100, '', ''],
]);
$tableSettings = json_decode((string) $tableWidget['settings_json'], true);
assertSameValue('Quatro colunas analíticas configuradas', 4, count($tableSettings['columns'] ?? []));
assertSameValue('Exportação PDF da tabela', 1, $tableSettings['export_pdf'] ?? null);
$tableRender = (new WidgetRenderer())->prepare(
    ['widget_type' => 'table', 'demo_data' => '[{"indicador":"Cumprimento do ANS","resultado":96.4,"historico":[91,93,92,96.4],"tempo":1254}]', 'settings' => $tableSettings],
    ['query_sql' => 'SELECT indicador, resultado, historico, tempo', 'row_limit' => 10],
    true,
    ['entity_id' => 2, 'date_start' => '2026-08-01', 'date_end' => '2026-08-31']
);
assertSameValue('Ordem das colunas analíticas', 'resultado', $tableRender['table']['columns'][1]['source'] ?? null);
assertSameValue('Resultado formatado na tabela', '96,4%', $tableRender['table']['rows'][0][1]['display'] ?? null);
assertSameValue('Percentual da barra de progresso', 96.4, $tableRender['table']['rows'][0][1]['percent'] ?? null);
assertSameValue('Série do minigráfico', [91.0, 93.0, 92.0, 96.4], $tableRender['table']['rows'][0][2]['series'] ?? null);
assertSameValue('Duração formatada na tabela', '00:20:54', $tableRender['table']['rows'][0][3]['display'] ?? null);

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

$numberWidget = DashboardWidget::validate([
    'savedqueries_id' => 1,
    'widget_type' => 'number',
    'number_decimals' => 2,
    'number_prefix' => 'R$ ',
    'number_suffix' => '',
    'number_target' => 95,
    'number_use_colors' => 1,
    'number_warning' => 80,
    'number_success' => 95,
    'number_color_low' => '#d63939',
    'number_color_mid' => '#f59f00',
    'number_color_high' => '#2fb344',
]);
$numberSettings = json_decode((string) $numberWidget['settings_json'], true);
assertSameValue('Casas decimais do indicador', 2, $numberSettings['decimals'] ?? null);
assertSameValue('Prefixo do indicador', 'R$ ', $numberSettings['prefix'] ?? null);
$formattedNumber = (new WidgetRenderer())->prepare(
    ['widget_type' => 'number', 'demo_data' => '[{"valor":92}]', 'settings' => $numberSettings],
    ['query_sql' => 'SELECT 92', 'row_limit' => 1],
    true,
    ['entity_id' => 2, 'date_start' => '2026-08-01', 'date_end' => '2026-08-31']
);
assertSameValue('Valor numérico formatado', 'R$ 92,00', $formattedNumber['number']['value'] ?? null);
assertSameValue('Cor condicional de atenção', '#f59f00', $formattedNumber['number']['color'] ?? null);
assertSameValue('Meta numérica formatada', 'R$ 95,00', $formattedNumber['number']['target'] ?? null);

$formattedDuration = (new WidgetRenderer())->prepare(
    ['widget_type' => 'number', 'demo_data' => '[{"media_resolucao":"00:20:54"}]'],
    ['query_sql' => 'SELECT "00:20:54"', 'row_limit' => 1],
    true,
    ['entity_id' => 2, 'date_start' => '2026-08-01', 'date_end' => '2026-08-31']
);
assertSameValue('Duração textual preservada', '00:20:54', $formattedDuration['number']['value'] ?? null);

foreach (
    [
        ['number_decimals' => 7],
        ['number_warning' => 95, 'number_success' => 80],
    ] as $invalidNumberSettings
) {
    try {
        DashboardWidget::validate(array_merge([
            'savedqueries_id' => 1,
            'widget_type' => 'number',
        ], $invalidNumberSettings));
        throw new RuntimeException('Configuração numérica inválida foi aceita.');
    } catch (InvalidArgumentException) {
        // Expected.
    }
}

foreach (
    [
        ['widget_type' => 'bar', 'bar_orientation' => 'diagonal'],
        ['widget_type' => 'line', 'line_decimals' => 7],
        ['widget_type' => 'bar', 'bar_color' => 'azul'],
        ['widget_type' => 'doughnut', 'doughnut_hole_size' => 90],
        ['widget_type' => 'doughnut', 'doughnut_legend_position' => 'left'],
    ] as $invalidChartSettings
) {
    try {
        DashboardWidget::validate(['savedqueries_id' => 1] + $invalidChartSettings);
        throw new RuntimeException('Configuração de gráfico inválida foi aceita.');
    } catch (InvalidArgumentException) {
        // Expected.
    }
}

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

assertSameValue(
    'Layout normalizado na ordem visual',
    [
        ['id' => 12, 'width' => 8, 'position' => 0],
        ['id' => 11, 'width' => 4, 'position' => 1],
    ],
    DashboardWidget::normalizeLayout([12, 11], [11 => 4, 12 => 8], [11, 12])
);
foreach (
    [
        [[11], [11 => 4], [11, 12]],
        [[11, 99], [11 => 4, 99 => 4], [11, 12]],
        [[11, 12], [11 => 5, 12 => 4], [11, 12]],
    ] as [$layoutIds, $layoutWidths, $allowedIds]
) {
    try {
        DashboardWidget::normalizeLayout($layoutIds, $layoutWidths, $allowedIds);
        throw new RuntimeException('Layout inválido foi aceito.');
    } catch (InvalidArgumentException) {
        // Expected.
    }
}

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
