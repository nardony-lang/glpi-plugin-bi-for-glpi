<?php

use GlpiPlugin\Biforglpi\Dashboard;
use GlpiPlugin\Biforglpi\DashboardAccess;
use GlpiPlugin\Biforglpi\DashboardWidget;
use GlpiPlugin\Biforglpi\Navigation;
use GlpiPlugin\Biforglpi\Profile as BiforglpiProfile;
use GlpiPlugin\Biforglpi\SavedQuery;
use GlpiPlugin\Biforglpi\SqlLab;

include '../../../inc/includes.php';

$pluginUrl = Plugin::getWebDir('biforglpi');
$escapedPluginUrl = htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8');
$id = filter_var($_POST['id'] ?? $_GET['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
if ($id > 0) {
    DashboardAccess::checkEdit($id);
} else {
    Session::checkRight(BiforglpiProfile::RIGHT_MANAGE_DASHBOARDS, UPDATE);
}
$error = null;
$redirectUrl = null;
$dashboard = ['id' => 0, 'name' => '', 'description' => '', 'is_active' => 1, 'is_demo' => 0];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        if (isset($_POST['delete']) && $id > 0) {
            Dashboard::delete($id);
            $redirectUrl = $pluginUrl . '/front/dashboards.php?deleted=1';
        }
        if (isset($_POST['save'])) {
            if ($id > 0) {
                Dashboard::update($id, $_POST);
            } else {
                $id = Dashboard::create($_POST);
            }
            $redirectUrl = $pluginUrl . '/front/dashboard.form.php?id=' . $id . '&saved=1';
        }
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
        $dashboard = array_merge($dashboard, $_POST, ['id' => $id]);
    } catch (Throwable) {
        $error = __('Não foi possível concluir a operação. Consulte os logs do GLPI.', 'biforglpi');
        $dashboard = array_merge($dashboard, $_POST, ['id' => $id]);
    }
    if ($redirectUrl !== null) {
        Html::redirect($redirectUrl);
    }
} elseif ($id > 0) {
    $dashboard = Dashboard::find($id) ?? $dashboard;
}

$widgets = $id > 0 ? DashboardWidget::allForDashboard($id) : [];
$queriesById = [];
foreach (SavedQuery::all() as $query) {
    $queriesById[(int) $query['id']] = $query;
}

Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'plugins', SqlLab::class);
?>
<main class="biforglpi-lab container-xl">
    <?php Navigation::render('dashboards'); ?>
    <div class="biforglpi-page-heading"><div><h1><?= $id ? __('Configurar dashboard', 'biforglpi') : __('Novo dashboard', 'biforglpi') ?></h1><p class="text-secondary mb-0"><?= __('Defina o painel, seus componentes e quem pode acessá-lo.', 'biforglpi') ?></p></div></div>
    <?php if (isset($_GET['saved'])): ?><div class="alert alert-success"><?= __('Dashboard salvo com sucesso.', 'biforglpi') ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <section class="card biforglpi-card"><div class="card-body">
        <form method="post" action="<?= $escapedPluginUrl ?>/front/dashboard.form.php">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="mb-3"><label class="form-label" for="dashboard-name"><?= __('Nome', 'biforglpi') ?></label><input class="form-control" id="dashboard-name" name="name" maxlength="255" required value="<?= htmlspecialchars((string) $dashboard['name'], ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="mb-3"><label class="form-label" for="dashboard-description"><?= __('Descrição', 'biforglpi') ?></label><textarea class="form-control" id="dashboard-description" name="description" maxlength="2000" rows="3"><?= htmlspecialchars((string) ($dashboard['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
            <div class="d-flex flex-wrap gap-4">
                <label class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= !empty($dashboard['is_active']) ? 'checked' : '' ?>><span class="form-check-label"><?= __('Dashboard ativo', 'biforglpi') ?></span></label>
                <label class="form-check"><input type="hidden" name="is_demo" value="0"><input class="form-check-input" type="checkbox" name="is_demo" value="1" <?= !empty($dashboard['is_demo']) ? 'checked' : '' ?>><span class="form-check-label"><?= __('Usar dados de demonstração', 'biforglpi') ?></span></label>
            </div>
            <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" name="save" value="1"><i class="ti ti-device-floppy"></i> <?= __('Salvar dashboard', 'biforglpi') ?></button><a class="btn btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/dashboards.php"><?= __('Voltar', 'biforglpi') ?></a></div>
            <?php Html::closeForm(); ?>
    </div></section>
    <?php if ($id > 0): ?>
        <section class="card biforglpi-card mt-4"><div class="card-header"><h2 class="card-title"><?= __('Componentes', 'biforglpi') ?></h2><a class="btn btn-primary" href="<?= $escapedPluginUrl ?>/front/widget.form.php?dashboards_id=<?= $id ?>"><i class="ti ti-plus"></i> <?= __('Adicionar componente', 'biforglpi') ?></a></div>
            <?php if ($widgets === []): ?><div class="card-body text-secondary"><?= __('Nenhum componente configurado.', 'biforglpi') ?></div><?php else: ?><div class="table-responsive"><table class="table table-vcenter mb-0"><thead><tr><th><?= __('Posição', 'biforglpi') ?></th><th><?= __('Título / consulta', 'biforglpi') ?></th><th><?= __('Tipo', 'biforglpi') ?></th><th><?= __('Largura', 'biforglpi') ?></th><th></th></tr></thead><tbody><?php foreach ($widgets as $widget): $query = $queriesById[$widget['savedqueries_id']] ?? null; ?><tr><td><?= $widget['position'] ?></td><td><strong><?= htmlspecialchars((string) ($widget['title'] ?: ($query['name'] ?? 'Consulta removida')), ENT_QUOTES, 'UTF-8') ?></strong><div class="small text-secondary"><?= htmlspecialchars((string) ($query['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></td><td><?= htmlspecialchars((string) $widget['widget_type'], ENT_QUOTES, 'UTF-8') ?></td><td><?= $widget['width'] ?>/12</td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/widget.form.php?id=<?= $widget['id'] ?>"><?= __('Editar', 'biforglpi') ?></a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        </section>
        <section class="card biforglpi-card mt-4"><div class="card-body d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="<?= $escapedPluginUrl ?>/front/dashboardrights.form.php?dashboards_id=<?= $id ?>"><i class="ti ti-shield-lock"></i> <?= __('Perfis e grupos autorizados', 'biforglpi') ?></a><a class="btn btn-outline-primary" href="<?= $escapedPluginUrl ?>/front/dashboard.php?id=<?= $id ?>"><i class="ti ti-eye"></i> <?= __('Visualizar dashboard', 'biforglpi') ?></a></div></section>
        <form class="mt-4" method="post" action="<?= $escapedPluginUrl ?>/front/dashboard.form.php"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn-outline-danger" name="delete" value="1"><i class="ti ti-trash"></i> <?= __('Excluir dashboard', 'biforglpi') ?></button><?php Html::closeForm(); ?>
    <?php endif; ?>
</main>
<?php Html::footer(); ?>
