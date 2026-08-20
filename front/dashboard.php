<?php

use GlpiPlugin\Biforglpi\Dashboard;
use GlpiPlugin\Biforglpi\DashboardAccess;
use GlpiPlugin\Biforglpi\DashboardFilter;
use GlpiPlugin\Biforglpi\DashboardWidget;
use GlpiPlugin\Biforglpi\Navigation;
use GlpiPlugin\Biforglpi\Profile as BiforglpiProfile;
use GlpiPlugin\Biforglpi\SavedQuery;
use GlpiPlugin\Biforglpi\SqlLab;
use GlpiPlugin\Biforglpi\WidgetRenderer;

include '../../../inc/includes.php';
Session::checkRight(BiforglpiProfile::RIGHT_VIEW_DASHBOARD, READ);

$pluginUrl = Plugin::getWebDir('biforglpi');
$escapedPluginUrl = htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8');
$dashboards = Dashboard::accessible();
$requestedId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$dashboard = null;
foreach ($dashboards as $candidate) {
    if ($requestedId === 0 || (int) $candidate['id'] === $requestedId) {
        $dashboard = $candidate;
        break;
    }
}
if ($requestedId > 0 && ($dashboard === null || (int) $dashboard['id'] !== $requestedId)) {
    http_response_code(403);
}

$widgets = $dashboard ? DashboardWidget::allForDashboard((int) $dashboard['id']) : [];
$queries = [];
foreach (SavedQuery::all() as $query) { $queries[(int) $query['id']] = $query; }
$renderer = new WidgetRenderer();
$filterError = null;
try {
    $filterContext = DashboardFilter::context($_GET);
} catch (InvalidArgumentException $exception) {
    $filterError = $exception->getMessage();
    $filterContext = DashboardFilter::context([]);
}
$filterEntities = DashboardFilter::entities();
$prepared = [];
foreach ($widgets as $widget) {
    $query = $queries[$widget['savedqueries_id']] ?? null;
    $prepared[$widget['id']] = $query === null
        ? ['ok' => false, 'message' => __('A consulta vinculada não existe mais.', 'biforglpi')]
        : $renderer->prepare($widget, $query, !empty($dashboard['is_demo']), $filterContext);
}

Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'tools', SqlLab::class);
?>
<main class="biforglpi-lab container-xl">
    <?php Navigation::render('dashboard'); ?>
    <?php if ($dashboard === null): ?>
        <div class="biforglpi-page-heading"><div><h1><?= __('Dashboard', 'biforglpi') ?></h1><p class="text-secondary mb-0"><?= __('Você ainda não possui um dashboard disponível.', 'biforglpi') ?></p></div></div>
        <section class="card biforglpi-card"><div class="card-body biforglpi-empty"><i class="ti ti-layout-dashboard-off"></i><h2><?= __('Nenhum dashboard disponível', 'biforglpi') ?></h2><a class="btn btn-primary" href="<?= $escapedPluginUrl ?>/front/dashboards.php"><?= __('Ver dashboards', 'biforglpi') ?></a></div></section>
    <?php else: ?>
        <div class="biforglpi-page-heading"><div><div class="d-flex gap-2 align-items-center"><h1 class="mb-0"><?= htmlspecialchars((string) $dashboard['name'], ENT_QUOTES, 'UTF-8') ?></h1><?php if ($dashboard['is_demo']): ?><span class="badge bg-azure-lt"><?= __('Demonstração', 'biforglpi') ?></span><?php endif; ?></div><?php if ($dashboard['description']): ?><p class="text-secondary mb-0 mt-1"><?= htmlspecialchars((string) $dashboard['description'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?></div><div class="biforglpi-dashboard-actions"><?php if (count($dashboards) > 1): ?><select class="form-select" aria-label="<?= __('Trocar dashboard', 'biforglpi') ?>" onchange="if(this.value){location.href=this.value}"><?php foreach ($dashboards as $choice): ?><option value="<?= $escapedPluginUrl ?>/front/dashboard.php?id=<?= $choice['id'] ?>" <?= $choice['id'] === $dashboard['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $choice['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select><?php endif; ?><?php if (DashboardAccess::canEdit((int) $dashboard['id'])): ?><a class="btn btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/dashboard.form.php?id=<?= $dashboard['id'] ?>"><i class="ti ti-settings"></i> <?= __('Configurar', 'biforglpi') ?></a><?php endif; ?></div></div>
        <?php if ($filterError !== null): ?><div class="alert alert-warning"><?= htmlspecialchars($filterError, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($dashboard['use_entity_filter'] || $dashboard['use_period_filter']): ?>
            <form class="card biforglpi-card biforglpi-filters mb-4" method="get" action="<?= $escapedPluginUrl ?>/front/dashboard.php">
                <div class="card-body"><input type="hidden" name="id" value="<?= (int) $dashboard['id'] ?>"><div class="row g-3 align-items-end">
                    <?php if ($dashboard['use_entity_filter']): ?><div class="col-lg-4"><label class="form-label" for="filter-entity"><?= __('Entidade', 'biforglpi') ?></label><select class="form-select" id="filter-entity" name="entity_id"><?php foreach ($filterEntities as $entityId => $entityName): ?><option value="<?= $entityId ?>" <?= $entityId === $filterContext['entity_id'] ? 'selected' : '' ?>><?= htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div><?php endif; ?>
                    <?php if ($dashboard['use_period_filter']): ?><div class="col-md-4 col-lg-3"><label class="form-label" for="filter-start"><?= __('Data inicial', 'biforglpi') ?></label><input class="form-control" id="filter-start" name="date_start" type="date" value="<?= htmlspecialchars($filterContext['date_start'], ENT_QUOTES, 'UTF-8') ?>"></div><div class="col-md-4 col-lg-3"><label class="form-label" for="filter-end"><?= __('Data final', 'biforglpi') ?></label><input class="form-control" id="filter-end" name="date_end" type="date" value="<?= htmlspecialchars($filterContext['date_end'], ENT_QUOTES, 'UTF-8') ?>"></div><?php endif; ?>
                    <div class="col-md-4 col-lg-2 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="ti ti-filter"></i> <?= __('Aplicar', 'biforglpi') ?></button><a class="btn btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/dashboard.php?id=<?= (int) $dashboard['id'] ?>" title="<?= __('Limpar filtros', 'biforglpi') ?>"><i class="ti ti-refresh"></i></a></div>
                </div></div>
            </form>
        <?php endif; ?>
        <?php if ($widgets === []): ?><section class="card biforglpi-card"><div class="card-body biforglpi-empty"><i class="ti ti-chart-bar-off"></i><h2><?= __('Nenhum componente configurado', 'biforglpi') ?></h2></div></section><?php endif; ?>
        <section class="biforglpi-widget-grid">
        <?php foreach ($widgets as $widget): $query = $queries[$widget['savedqueries_id']] ?? []; $result = $prepared[$widget['id']]; $title = $widget['title'] ?: ($query['name'] ?? __('Componente', 'biforglpi')); ?>
            <article class="card biforglpi-card biforglpi-widget" id="biforglpi-widget-<?= (int) $widget['id'] ?>" style="--biforglpi-width: <?= $widget['width'] ?>" <?= $widget['widget_type'] === 'table' ? 'data-table-widget data-export-name="' . htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                <div class="card-header"><h2 class="card-title"><?= htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') ?></h2><?php if (($result['elapsed_ms'] ?? null) !== null): ?><span class="small text-secondary"><?= $result['elapsed_ms'] ?> ms</span><?php endif; ?></div>
                <div class="card-body">
                <?php if (!$result['ok']): ?><div class="alert alert-danger mb-0"><?= htmlspecialchars((string) $result['message'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php elseif ($widget['widget_type'] === 'number'): ?><div class="biforglpi-indicator-value <?= $result['empty'] ? 'text-secondary' : '' ?>"<?php if ($result['number']['color'] !== null): ?> style="color: <?= htmlspecialchars((string) $result['number']['color'], ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>><?= htmlspecialchars((string) $result['value'], ENT_QUOTES, 'UTF-8') ?></div><?php if ($result['number']['target'] !== null): ?><div class="small text-secondary mt-2"><?= sprintf(__('Meta: %s', 'biforglpi'), htmlspecialchars((string) $result['number']['target'], ENT_QUOTES, 'UTF-8')) ?></div><?php endif; ?>
                <?php elseif ($widget['widget_type'] === 'gauge'): ?>
                    <?php if ($result['gauge'] === null): ?><div class="biforglpi-no-data"><?= __('Sem valor numérico para gerar o Gauge.', 'biforglpi') ?></div><?php else: ?><div class="biforglpi-chart biforglpi-gauge" data-chart-type="gauge" data-chart='<?= htmlspecialchars(json_encode($result['gauge'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>'><canvas role="img" aria-label="<?= htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') ?>"></canvas></div><?php endif; ?>
                <?php elseif ($widget['widget_type'] === 'table'): ?>
                    <?php if ($result['empty']): ?><div class="biforglpi-no-data"><?= __('Sem dados para o período selecionado.', 'biforglpi') ?></div><?php else: $tableData = $result['table']; $tableSettings = $tableData['settings']; ?>
                    <?php if (!empty($tableSettings['export_png']) || !empty($tableSettings['export_pdf'])): ?><div class="biforglpi-table-toolbar mb-3" data-html2canvas-ignore="true"><?php if (!empty($tableSettings['export_png'])): ?><button class="btn btn-sm btn-outline-secondary" type="button" data-table-export="png"><i class="ti ti-photo"></i> <?= __('Exportar PNG', 'biforglpi') ?></button><?php endif; ?><?php if (!empty($tableSettings['export_pdf'])): ?><button class="btn btn-sm btn-outline-secondary" type="button" data-table-export="pdf"><i class="ti ti-file-type-pdf"></i> <?= __('Exportar PDF', 'biforglpi') ?></button><?php endif; ?></div><?php endif; ?>
                    <div class="table-responsive biforglpi-analytics-table-wrap <?= !empty($tableSettings['sticky_header']) ? 'biforglpi-sticky-table' : '' ?>"><table class="table <?= !empty($tableSettings['compact']) ? 'table-sm' : '' ?> <?= !empty($tableSettings['striped']) ? 'table-striped' : '' ?> table-vcenter mb-0 biforglpi-analytics-table"><thead><tr><?php foreach ($tableData['columns'] as $column): ?><th scope="col" class="text-<?= htmlspecialchars((string) $column['align'], ENT_QUOTES, 'UTF-8') ?>"<?= (int) $column['width'] > 0 ? ' style="width:' . (int) $column['width'] . 'px;min-width:' . (int) $column['width'] . 'px"' : '' ?>><?= htmlspecialchars((string) $column['label'], ENT_QUOTES, 'UTF-8') ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($tableData['rows'] as $tableRow): ?><tr><?php foreach ($tableRow as $index => $cell): $column = $tableData['columns'][$index]; ?><td class="text-<?= htmlspecialchars((string) $column['align'], ENT_QUOTES, 'UTF-8') ?> biforglpi-cell-<?= htmlspecialchars((string) $cell['type'], ENT_QUOTES, 'UTF-8') ?>"<?= (int) $column['width'] > 0 ? ' style="width:' . (int) $column['width'] . 'px;min-width:' . (int) $column['width'] . 'px"' : '' ?>><?php if ($cell['type'] === 'badge'): ?><span class="badge bg-<?= htmlspecialchars((string) $cell['badge_color'], ENT_QUOTES, 'UTF-8') ?>-lt"><?= htmlspecialchars((string) $cell['display'], ENT_QUOTES, 'UTF-8') ?></span><?php elseif ($cell['type'] === 'progress'): ?><div class="biforglpi-progress-cell"><div class="d-flex justify-content-between gap-2 small"><span><?= htmlspecialchars((string) $cell['display'], ENT_QUOTES, 'UTF-8') ?></span><span><?= number_format((float) $cell['percent'], 0, ',', '.') ?>%</span></div><div class="progress progress-sm"><div class="progress-bar" style="width:<?= (float) $cell['percent'] ?>%;background-color:<?= htmlspecialchars((string) $cell['color'], ENT_QUOTES, 'UTF-8') ?>"></div></div></div><?php elseif ($cell['type'] === 'sparkline_line' || $cell['type'] === 'sparkline_bar'): ?><div class="biforglpi-sparkline-cell"><svg class="biforglpi-sparkline" role="img" aria-label="<?= htmlspecialchars((string) $column['label'] . ': ' . (string) $cell['display'], ENT_QUOTES, 'UTF-8') ?>" data-sparkline-type="<?= $cell['type'] === 'sparkline_bar' ? 'bar' : 'line' ?>" data-sparkline-color="<?= htmlspecialchars((string) $cell['color'], ENT_QUOTES, 'UTF-8') ?>" data-sparkline-values='<?= htmlspecialchars(json_encode($cell['series'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>' viewBox="0 0 120 36" preserveAspectRatio="none"></svg><span class="small text-secondary"><?= htmlspecialchars((string) $cell['display'], ENT_QUOTES, 'UTF-8') ?></span></div><?php else: ?><?= htmlspecialchars((string) $cell['display'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($result['chart']['labels'] === []): ?><div class="biforglpi-no-data"><?= __('Sem dados para gerar o gráfico.', 'biforglpi') ?></div><?php else: ?><div class="biforglpi-chart" data-chart-type="<?= htmlspecialchars((string) $widget['widget_type'], ENT_QUOTES, 'UTF-8') ?>" data-chart='<?= htmlspecialchars(json_encode($result['chart'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>'><canvas role="img" aria-label="<?= htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') ?>"></canvas></div><?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($query['description'])): ?><p class="small text-secondary mt-3 mb-0"><?= htmlspecialchars((string) $query['description'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php Html::footer(); ?>
