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
$dashboard = ['id' => 0, 'name' => '', 'description' => '', 'is_active' => 1, 'is_demo' => 0, 'use_entity_filter' => 1, 'use_period_filter' => 1];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        $duplicateWidgetId = filter_var($_POST['duplicate_widget'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $deleteWidgetId = filter_var($_POST['delete_widget'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        if ($duplicateWidgetId > 0) {
            $actionWidget = DashboardWidget::find($duplicateWidgetId);
            if ($actionWidget === null || (int) $actionWidget['dashboards_id'] !== $id) {
                throw new InvalidArgumentException('O componente não pertence a este dashboard.');
            }
            DashboardWidget::duplicate($duplicateWidgetId);
            $redirectUrl = $pluginUrl . '/front/dashboard.form.php?id=' . $id . '&duplicated=1';
        } elseif ($deleteWidgetId > 0) {
            $actionWidget = DashboardWidget::find($deleteWidgetId);
            if ($actionWidget === null || (int) $actionWidget['dashboards_id'] !== $id) {
                throw new InvalidArgumentException('O componente não pertence a este dashboard.');
            }
            DashboardWidget::delete($deleteWidgetId);
            $redirectUrl = $pluginUrl . '/front/dashboard.form.php?id=' . $id . '&widget_deleted=1';
        } elseif (isset($_POST['save_layout'])) {
            DashboardWidget::updateLayout(
                $id,
                is_array($_POST['widget_ids'] ?? null) ? $_POST['widget_ids'] : [],
                is_array($_POST['widths'] ?? null) ? $_POST['widths'] : []
            );
            $redirectUrl = $pluginUrl . '/front/dashboard.form.php?id=' . $id . '&layout_saved=1';
        } elseif (isset($_POST['delete']) && $id > 0) {
            Dashboard::delete($id);
            $redirectUrl = $pluginUrl . '/front/dashboards.php?deleted=1';
        } elseif (isset($_POST['save'])) {
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

Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'tools', SqlLab::class);
?>
<main class="biforglpi-lab container-xl">
    <?php Navigation::render('dashboards'); ?>
    <div class="biforglpi-page-heading"><div><h1><?= $id ? __('Configurar dashboard', 'biforglpi') : __('Novo dashboard', 'biforglpi') ?></h1><p class="text-secondary mb-0"><?= __('Defina o painel, seus componentes e quem pode acessá-lo.', 'biforglpi') ?></p></div></div>
    <?php if (isset($_GET['saved'])): ?><div class="alert alert-success"><?= __('Dashboard salvo com sucesso.', 'biforglpi') ?></div><?php endif; ?>
    <?php if (isset($_GET['layout_saved'])): ?><div class="alert alert-success"><?= __('Layout salvo com sucesso.', 'biforglpi') ?></div><?php endif; ?>
    <?php if (isset($_GET['duplicated'])): ?><div class="alert alert-success"><?= __('Componente duplicado com sucesso.', 'biforglpi') ?></div><?php endif; ?>
    <?php if (isset($_GET['widget_deleted'])): ?><div class="alert alert-success"><?= __('Componente excluído com sucesso.', 'biforglpi') ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <section class="card biforglpi-card"><div class="card-body">
        <form method="post" action="<?= $escapedPluginUrl ?>/front/dashboard.form.php">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="mb-3"><label class="form-label" for="dashboard-name"><?= __('Nome', 'biforglpi') ?></label><input class="form-control" id="dashboard-name" name="name" maxlength="255" required value="<?= htmlspecialchars((string) $dashboard['name'], ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="mb-3"><label class="form-label" for="dashboard-description"><?= __('Descrição', 'biforglpi') ?></label><textarea class="form-control" id="dashboard-description" name="description" maxlength="2000" rows="3"><?= htmlspecialchars((string) ($dashboard['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
            <div class="d-flex flex-wrap gap-4">
                <label class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= !empty($dashboard['is_active']) ? 'checked' : '' ?>><span class="form-check-label"><?= __('Dashboard ativo', 'biforglpi') ?></span></label>
                <label class="form-check"><input type="hidden" name="is_demo" value="0"><input class="form-check-input" type="checkbox" name="is_demo" value="1" <?= !empty($dashboard['is_demo']) ? 'checked' : '' ?>><span class="form-check-label"><?= __('Usar dados de demonstração', 'biforglpi') ?></span></label>
                <label class="form-check"><input type="hidden" name="use_entity_filter" value="0"><input class="form-check-input" type="checkbox" name="use_entity_filter" value="1" <?= !empty($dashboard['use_entity_filter']) ? 'checked' : '' ?>><span class="form-check-label"><?= __('Filtro de entidade', 'biforglpi') ?></span></label>
                <label class="form-check"><input type="hidden" name="use_period_filter" value="0"><input class="form-check-input" type="checkbox" name="use_period_filter" value="1" <?= !empty($dashboard['use_period_filter']) ? 'checked' : '' ?>><span class="form-check-label"><?= __('Filtro de período', 'biforglpi') ?></span></label>
            </div>
            <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" name="save" value="1"><i class="ti ti-device-floppy"></i> <?= __('Salvar dashboard', 'biforglpi') ?></button><a class="btn btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/dashboards.php"><?= __('Voltar', 'biforglpi') ?></a></div>
            <?php Html::closeForm(); ?>
    </div></section>
    <?php if ($id > 0): ?>
        <section class="card biforglpi-card mt-4"><div class="card-header"><div><h2 class="card-title"><?= __('Editor visual', 'biforglpi') ?></h2><p class="small text-secondary mb-0"><?= __('Arraste os componentes, ajuste as larguras e salve o layout.', 'biforglpi') ?></p></div><a class="btn btn-primary" href="<?= $escapedPluginUrl ?>/front/widget.form.php?dashboards_id=<?= $id ?>"><i class="ti ti-plus"></i> <?= __('Adicionar componente', 'biforglpi') ?></a></div>
            <?php if ($widgets === []): ?><div class="card-body text-secondary"><?= __('Nenhum componente configurado.', 'biforglpi') ?></div><?php else: ?>
            <form method="post" action="<?= $escapedPluginUrl ?>/front/dashboard.form.php" id="biforglpi-layout-form"><input type="hidden" name="id" value="<?= $id ?>">
                <div class="card-body"><div class="biforglpi-builder-grid" id="biforglpi-builder-grid">
                    <?php foreach ($widgets as $widget): $query = $queriesById[$widget['savedqueries_id']] ?? null; $widgetTitle = $widget['title'] ?: ($query['name'] ?? __('Consulta removida', 'biforglpi')); ?>
                    <article class="card biforglpi-builder-item" draggable="true" data-widget-id="<?= (int) $widget['id'] ?>" style="--biforglpi-builder-width: <?= (int) $widget['width'] ?>">
                        <input type="hidden" name="widget_ids[]" value="<?= (int) $widget['id'] ?>">
                        <div class="card-header"><button class="btn btn-icon btn-ghost-secondary biforglpi-drag-handle" type="button" title="<?= __('Arrastar componente', 'biforglpi') ?>"><i class="ti ti-grip-vertical"></i></button><div class="flex-fill min-width-0"><strong class="d-block text-truncate"><?= htmlspecialchars((string) $widgetTitle, ENT_QUOTES, 'UTF-8') ?></strong><span class="small text-secondary"><?= htmlspecialchars((string) ($query['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></div><span class="badge bg-azure-lt"><?= htmlspecialchars((string) $widget['widget_type'], ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="card-body"><div class="row g-2 align-items-end"><div class="col-sm-5"><label class="form-label" for="width-<?= (int) $widget['id'] ?>"><?= __('Largura', 'biforglpi') ?></label><select class="form-select biforglpi-builder-width" id="width-<?= (int) $widget['id'] ?>" name="widths[<?= (int) $widget['id'] ?>]"><?php foreach ([3,4,6,8,12] as $width): ?><option value="<?= $width ?>" <?= (int) $widget['width'] === $width ? 'selected' : '' ?>><?= $width ?>/12</option><?php endforeach; ?></select></div><div class="col-sm-7"><div class="btn-list justify-content-end"><button class="btn btn-icon btn-outline-secondary biforglpi-move-up" type="button" title="<?= __('Mover para cima', 'biforglpi') ?>"><i class="ti ti-arrow-up"></i></button><button class="btn btn-icon btn-outline-secondary biforglpi-move-down" type="button" title="<?= __('Mover para baixo', 'biforglpi') ?>"><i class="ti ti-arrow-down"></i></button><a class="btn btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/widget.form.php?id=<?= (int) $widget['id'] ?>"><i class="ti ti-pencil"></i> <?= __('Editar', 'biforglpi') ?></a><button class="btn btn-outline-secondary" name="duplicate_widget" value="<?= (int) $widget['id'] ?>"><i class="ti ti-copy"></i> <?= __('Duplicar', 'biforglpi') ?></button><button class="btn btn-outline-danger biforglpi-delete-widget" name="delete_widget" value="<?= (int) $widget['id'] ?>" data-widget-title="<?= htmlspecialchars((string) $widgetTitle, ENT_QUOTES, 'UTF-8') ?>"><i class="ti ti-trash"></i></button></div></div></div></div>
                    </article>
                    <?php endforeach; ?>
                </div></div>
                <div class="card-footer d-flex justify-content-end"><button class="btn btn-primary" name="save_layout" value="1"><i class="ti ti-device-floppy"></i> <?= __('Salvar layout', 'biforglpi') ?></button></div>
                <?php Html::closeForm(); ?>
            <?php endif; ?>
        </section>
        <section class="card biforglpi-card mt-4"><div class="card-body d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="<?= $escapedPluginUrl ?>/front/dashboardrights.form.php?dashboards_id=<?= $id ?>"><i class="ti ti-shield-lock"></i> <?= __('Perfis e grupos autorizados', 'biforglpi') ?></a><a class="btn btn-outline-primary" href="<?= $escapedPluginUrl ?>/front/dashboard.php?id=<?= $id ?>"><i class="ti ti-eye"></i> <?= __('Visualizar dashboard', 'biforglpi') ?></a></div></section>
        <form class="mt-4" method="post" action="<?= $escapedPluginUrl ?>/front/dashboard.form.php"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn-outline-danger" name="delete" value="1"><i class="ti ti-trash"></i> <?= __('Excluir dashboard', 'biforglpi') ?></button><?php Html::closeForm(); ?>
    <?php endif; ?>
</main>
<?php Html::footer(); ?>
