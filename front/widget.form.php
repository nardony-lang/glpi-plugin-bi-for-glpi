<?php

use GlpiPlugin\Biforglpi\DashboardAccess;
use GlpiPlugin\Biforglpi\DashboardWidget;
use GlpiPlugin\Biforglpi\Navigation;
use GlpiPlugin\Biforglpi\SavedQuery;
use GlpiPlugin\Biforglpi\SqlLab;

include '../../../inc/includes.php';
$pluginUrl = Plugin::getWebDir('biforglpi');
$escapedPluginUrl = htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8');
$id = filter_var($_POST['id'] ?? $_GET['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$dashboardId = filter_var($_POST['dashboards_id'] ?? $_GET['dashboards_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$widget = ['id' => 0, 'savedqueries_id' => 0, 'title' => '', 'widget_type' => 'number', 'position' => 0, 'width' => 4, 'demo_data' => ''];
if ($id > 0) {
    $stored = DashboardWidget::find($id);
    if ($stored === null) { http_response_code(404); exit; }
    $widget = $stored;
    $dashboardId = (int) $widget['dashboards_id'];
}
DashboardAccess::checkEdit($dashboardId);
$error = null;
$redirectUrl = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        if (isset($_POST['delete']) && $id > 0) {
            DashboardWidget::delete($id);
        } elseif (isset($_POST['save'])) {
            $id > 0 ? DashboardWidget::update($id, $_POST) : DashboardWidget::create($dashboardId, $_POST);
        }
        $redirectUrl = $pluginUrl . '/front/dashboard.form.php?id=' . $dashboardId;
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
        $widget = array_merge($widget, $_POST);
    } catch (Throwable) {
        $error = __('Não foi possível concluir a operação.', 'biforglpi');
        $widget = array_merge($widget, $_POST);
    }
    if ($redirectUrl !== null) {
        Html::redirect($redirectUrl);
    }
}
$queries = SavedQuery::all(true);
$labels = ['number' => 'Indicador numérico', 'table' => 'Tabela', 'bar' => 'Gráfico de barras', 'line' => 'Gráfico de linha', 'doughnut' => 'Gráfico de rosca'];
Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'tools', SqlLab::class);
?>
<main class="biforglpi-lab container-xl"><?php Navigation::render('dashboards'); ?><div class="biforglpi-page-heading"><div><h1><?= $id ? __('Editar componente', 'biforglpi') : __('Novo componente', 'biforglpi') ?></h1><p class="text-secondary mb-0"><?= __('Associe uma consulta a uma visualização do dashboard.', 'biforglpi') ?></p></div></div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<section class="card biforglpi-card"><div class="card-body"><form method="post" action="<?= $escapedPluginUrl ?>/front/widget.form.php"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="dashboards_id" value="<?= $dashboardId ?>">
<div class="mb-3"><label class="form-label" for="widget-query"><?= __('Consulta salva', 'biforglpi') ?></label><select class="form-select" id="widget-query" name="savedqueries_id" required><option value=""><?= __('Selecione', 'biforglpi') ?></option><?php foreach ($queries as $query): ?><option value="<?= $query['id'] ?>" <?= (int) $widget['savedqueries_id'] === (int) $query['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $query['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label" for="widget-title"><?= __('Título opcional', 'biforglpi') ?></label><input class="form-control" id="widget-title" name="title" maxlength="255" value="<?= htmlspecialchars((string) ($widget['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
<div class="row g-3"><div class="col-md-4"><label class="form-label" for="widget-type"><?= __('Visualização', 'biforglpi') ?></label><select class="form-select" id="widget-type" name="widget_type"><?php foreach ($labels as $type => $label): ?><option value="<?= $type ?>" <?= $widget['widget_type'] === $type ? 'selected' : '' ?>><?= __($label, 'biforglpi') ?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label" for="widget-width"><?= __('Largura', 'biforglpi') ?></label><select class="form-select" id="widget-width" name="width"><?php foreach ([3,4,6,8,12] as $width): ?><option value="<?= $width ?>" <?= (int) $widget['width'] === $width ? 'selected' : '' ?>><?= $width ?>/12</option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label" for="widget-position"><?= __('Posição', 'biforglpi') ?></label><input class="form-control" id="widget-position" name="position" type="number" min="0" max="999" value="<?= (int) $widget['position'] ?>"></div></div>
<div class="mt-3"><label class="form-label" for="widget-demo"><?= __('Dados de demonstração (JSON)', 'biforglpi') ?></label><textarea class="form-control font-monospace" id="widget-demo" name="demo_data" rows="7" placeholder='[{"mes":"Jan","valor":18}]'><?= htmlspecialchars((string) ($widget['demo_data'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea><div class="form-hint"><?= __('Use uma lista de objetos. Para gráficos, a primeira coluna será o rótulo e a segunda o valor.', 'biforglpi') ?></div></div>
<div class="mt-4 d-flex gap-2"><button class="btn btn-primary" name="save" value="1"><?= __('Salvar componente', 'biforglpi') ?></button><a class="btn btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/dashboard.form.php?id=<?= $dashboardId ?>"><?= __('Cancelar', 'biforglpi') ?></a><?php if ($id): ?><button class="btn btn-outline-danger ms-auto" name="delete" value="1"><?= __('Excluir', 'biforglpi') ?></button><?php endif; ?></div><?php Html::closeForm(); ?></div></section></main>
<?php Html::footer(); ?>
