<?php

use GlpiPlugin\Biforglpi\Dashboard;
use GlpiPlugin\Biforglpi\DashboardAccess;
use GlpiPlugin\Biforglpi\DashboardWidget;
use GlpiPlugin\Biforglpi\IndicatorCatalog;
use GlpiPlugin\Biforglpi\Navigation;
use GlpiPlugin\Biforglpi\Profile as BiforglpiProfile;
use GlpiPlugin\Biforglpi\SavedQuery;
use GlpiPlugin\Biforglpi\SqlLab;

include '../../../inc/includes.php';
Session::checkRight(BiforglpiProfile::RIGHT_MANAGE_QUERIES, UPDATE);

$pluginUrl = Plugin::getWebDir('biforglpi');
$escapedPluginUrl = htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8');
$templates = IndicatorCatalog::all();
$dashboards = array_values(array_filter(Dashboard::accessible(), static fn(array $dashboard): bool => DashboardAccess::canEdit((int) $dashboard['id'])));
$error = null;
$redirectUrl = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        $templateKey = (string) ($_POST['template'] ?? '');
        $template = IndicatorCatalog::find($templateKey);
        $dashboardId = filter_var($_POST['dashboards_id'] ?? null, FILTER_VALIDATE_INT);
        if ($template === null || $dashboardId === false || $dashboardId < 1) {
            throw new InvalidArgumentException('Selecione um indicador e um dashboard.');
        }
        DashboardAccess::checkEdit((int) $dashboardId);
        $queryId = SavedQuery::create($template + ['is_active' => 1]);
        DashboardWidget::create((int) $dashboardId, [
            'savedqueries_id' => $queryId,
            'title' => $template['name'],
            'widget_type' => $template['widget_type'],
            'position' => 100,
            'width' => $template['width'],
            'demo_data' => '',
            'settings_json' => isset($template['widget_settings'])
                ? json_encode($template['widget_settings'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
        ]);
        $redirectUrl = $pluginUrl . '/front/dashboard.php?id=' . (int) $dashboardId;
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable) {
        $error = __('Não foi possível adicionar o indicador.', 'biforglpi');
    }
    if ($redirectUrl !== null) {
        Html::redirect($redirectUrl);
    }
}

Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'tools', SqlLab::class);
?>
<main class="biforglpi-lab container-xl"><?php Navigation::render('catalog'); ?>
<div class="biforglpi-page-heading"><div><h1><?= __('Catálogo de indicadores', 'biforglpi') ?></h1><p class="text-secondary mb-0"><?= __('Modelos prontos que respeitam os filtros de entidade e período.', 'biforglpi') ?></p></div></div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($dashboards === []): ?><div class="alert alert-warning"><?= __('Crie um dashboard ou solicite permissão de edição antes de adicionar indicadores.', 'biforglpi') ?></div><?php endif; ?>
<section class="biforglpi-dashboard-list"><?php foreach ($templates as $key => $template): ?><article class="card biforglpi-card"><div class="card-body"><h2 class="h3"><?= htmlspecialchars((string) $template['name'], ENT_QUOTES, 'UTF-8') ?></h2><p class="text-secondary"><?= htmlspecialchars((string) $template['description'], ENT_QUOTES, 'UTF-8') ?></p><form method="post" action="<?= $escapedPluginUrl ?>/front/catalog.php"><input type="hidden" name="template" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><label class="form-label"><?= __('Adicionar ao dashboard', 'biforglpi') ?></label><div class="d-flex gap-2"><select class="form-select" name="dashboards_id" required><option value=""><?= __('Selecione', 'biforglpi') ?></option><?php foreach ($dashboards as $dashboard): ?><option value="<?= $dashboard['id'] ?>"><?= htmlspecialchars((string) $dashboard['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select><button class="btn btn-primary" <?= $dashboards === [] ? 'disabled' : '' ?>><i class="ti ti-plus"></i> <?= __('Adicionar', 'biforglpi') ?></button></div><?php Html::closeForm(); ?></div></article><?php endforeach; ?></section>
</main><?php Html::footer(); ?>
