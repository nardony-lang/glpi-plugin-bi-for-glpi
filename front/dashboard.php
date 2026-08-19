<?php

use GlpiPlugin\Biforglpi\Dashboard;
use GlpiPlugin\Biforglpi\DashboardAccess;
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
$prepared = [];
foreach ($widgets as $widget) {
    $query = $queries[$widget['savedqueries_id']] ?? null;
    $prepared[$widget['id']] = $query === null
        ? ['ok' => false, 'message' => __('A consulta vinculada não existe mais.', 'biforglpi')]
        : $renderer->prepare($widget, $query, !empty($dashboard['is_demo']));
}

Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'plugins', SqlLab::class);
?>
<main class="biforglpi-lab container-xl">
    <?php Navigation::render('dashboard'); ?>
    <?php if ($dashboard === null): ?>
        <div class="biforglpi-page-heading"><div><h1><?= __('Dashboard', 'biforglpi') ?></h1><p class="text-secondary mb-0"><?= __('Você ainda não possui um dashboard disponível.', 'biforglpi') ?></p></div></div>
        <section class="card biforglpi-card"><div class="card-body biforglpi-empty"><i class="ti ti-layout-dashboard-off"></i><h2><?= __('Nenhum dashboard disponível', 'biforglpi') ?></h2><a class="btn btn-primary" href="<?= $escapedPluginUrl ?>/front/dashboards.php"><?= __('Ver dashboards', 'biforglpi') ?></a></div></section>
    <?php else: ?>
        <div class="biforglpi-page-heading"><div><div class="d-flex gap-2 align-items-center"><h1 class="mb-0"><?= htmlspecialchars((string) $dashboard['name'], ENT_QUOTES, 'UTF-8') ?></h1><?php if ($dashboard['is_demo']): ?><span class="badge bg-azure-lt"><?= __('Demonstração', 'biforglpi') ?></span><?php endif; ?></div><?php if ($dashboard['description']): ?><p class="text-secondary mb-0 mt-1"><?= htmlspecialchars((string) $dashboard['description'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?></div><div class="d-flex gap-2"><?php if (count($dashboards) > 1): ?><select class="form-select" aria-label="<?= __('Trocar dashboard', 'biforglpi') ?>" onchange="if(this.value){location.href=this.value}"><?php foreach ($dashboards as $choice): ?><option value="<?= $escapedPluginUrl ?>/front/dashboard.php?id=<?= $choice['id'] ?>" <?= $choice['id'] === $dashboard['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $choice['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select><?php endif; ?><?php if (DashboardAccess::canEdit((int) $dashboard['id'])): ?><a class="btn btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/dashboard.form.php?id=<?= $dashboard['id'] ?>"><i class="ti ti-settings"></i> <?= __('Configurar', 'biforglpi') ?></a><?php endif; ?></div></div>
        <?php if ($widgets === []): ?><section class="card biforglpi-card"><div class="card-body biforglpi-empty"><i class="ti ti-chart-bar-off"></i><h2><?= __('Nenhum componente configurado', 'biforglpi') ?></h2></div></section><?php endif; ?>
        <section class="biforglpi-widget-grid">
        <?php foreach ($widgets as $widget): $query = $queries[$widget['savedqueries_id']] ?? []; $result = $prepared[$widget['id']]; $title = $widget['title'] ?: ($query['name'] ?? __('Componente', 'biforglpi')); ?>
            <article class="card biforglpi-card biforglpi-widget" style="--biforglpi-width: <?= $widget['width'] ?>">
                <div class="card-header"><h2 class="card-title"><?= htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') ?></h2><?php if (($result['elapsed_ms'] ?? null) !== null): ?><span class="small text-secondary"><?= $result['elapsed_ms'] ?> ms</span><?php endif; ?></div>
                <div class="card-body">
                <?php if (!$result['ok']): ?><div class="alert alert-danger mb-0"><?= htmlspecialchars((string) $result['message'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php elseif ($widget['widget_type'] === 'number'): ?><div class="biforglpi-indicator-value <?= $result['empty'] ? 'text-secondary' : '' ?>"><?= htmlspecialchars((string) $result['value'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php elseif ($widget['widget_type'] === 'table'): ?>
                    <?php if ($result['empty']): ?><div class="biforglpi-no-data"><?= __('Sem dados para o período selecionado.', 'biforglpi') ?></div><?php else: ?><div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><?php foreach ($result['columns'] as $column): ?><th><?= htmlspecialchars((string) $column, ENT_QUOTES, 'UTF-8') ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($result['rows'] as $row): ?><tr><?php foreach ($result['columns'] as $column): ?><td><?= htmlspecialchars((string) ($row[$column] ?? ''), ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
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
