<?php

use GlpiPlugin\Biforglpi\Dashboard;
use GlpiPlugin\Biforglpi\DashboardAccess;
use GlpiPlugin\Biforglpi\Navigation;
use GlpiPlugin\Biforglpi\SqlLab;

include '../../../inc/includes.php';
$pluginUrl = Plugin::getWebDir('biforglpi');
$escapedPluginUrl = htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8');
$dashboardId = filter_var($_POST['dashboards_id'] ?? $_GET['dashboards_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
DashboardAccess::checkEdit($dashboardId);
$dashboard = Dashboard::find($dashboardId);
if ($dashboard === null) { http_response_code(404); exit; }
$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        if (isset($_POST['remove'])) {
            DashboardAccess::delete($dashboardId, (int) $_POST['remove']);
        } elseif (isset($_POST['save'])) {
            DashboardAccess::save($dashboardId, $_POST);
        }
        Html::redirect($pluginUrl . '/front/dashboardrights.form.php?dashboards_id=' . $dashboardId . '&saved=1');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable) {
        $error = __('Não foi possível concluir a operação.', 'biforglpi');
    }
}

$profiles = [];
foreach ($DB->request(['FROM' => 'glpi_profiles', 'ORDER' => ['name ASC']]) as $row) { $profiles[(int) $row['id']] = (string) $row['name']; }
$groups = [];
foreach ($DB->request(['FROM' => 'glpi_groups', 'ORDER' => ['completename ASC']]) as $row) { $groups[(int) $row['id']] = (string) ($row['completename'] ?: $row['name']); }
$rights = DashboardAccess::allForDashboard($dashboardId);
Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'plugins', SqlLab::class);
?>
<main class="biforglpi-lab container-xl"><?php Navigation::render('dashboards'); ?><div class="biforglpi-page-heading"><div><h1><?= __('Acesso ao dashboard', 'biforglpi') ?></h1><p class="text-secondary mb-0"><?= htmlspecialchars((string) $dashboard['name'], ENT_QUOTES, 'UTF-8') ?></p></div><a class="btn btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/dashboard.form.php?id=<?= $dashboardId ?>"><?= __('Voltar à configuração', 'biforglpi') ?></a></div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success"><?= __('Permissões atualizadas.', 'biforglpi') ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<section class="card biforglpi-card"><div class="card-header"><h2 class="card-title"><?= __('Perfis e grupos autorizados', 'biforglpi') ?></h2></div><?php if ($rights === []): ?><div class="card-body text-secondary"><?= __('Somente administradores globais do plugin e o proprietário têm acesso neste momento.', 'biforglpi') ?></div><?php else: ?><div class="table-responsive"><table class="table table-vcenter mb-0"><thead><tr><th><?= __('Tipo', 'biforglpi') ?></th><th><?= __('Nome', 'biforglpi') ?></th><th><?= __('Nível', 'biforglpi') ?></th><th></th></tr></thead><tbody><?php foreach ($rights as $right): $names = $right['itemtype'] === DashboardAccess::ITEM_PROFILE ? $profiles : $groups; ?><tr><td><?= $right['itemtype'] === DashboardAccess::ITEM_PROFILE ? __('Perfil', 'biforglpi') : __('Grupo', 'biforglpi') ?></td><td><?= htmlspecialchars((string) ($names[$right['items_id']] ?? ('#' . $right['items_id'])), ENT_QUOTES, 'UTF-8') ?></td><td><?= ($right['rights'] & UPDATE) === UPDATE ? __('Visualizar e editar', 'biforglpi') : __('Somente visualizar', 'biforglpi') ?></td><td class="text-end"><form method="post" action="<?= $escapedPluginUrl ?>/front/dashboardrights.form.php"><input type="hidden" name="dashboards_id" value="<?= $dashboardId ?>"><button class="btn btn-sm btn-outline-danger" name="remove" value="<?= $right['id'] ?>"><?= __('Remover', 'biforglpi') ?></button><?php Html::closeForm(); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="card biforglpi-card mt-4"><div class="card-header"><h2 class="card-title"><?= __('Adicionar ou alterar acesso', 'biforglpi') ?></h2></div><div class="card-body"><form method="post" action="<?= $escapedPluginUrl ?>/front/dashboardrights.form.php"><input type="hidden" name="dashboards_id" value="<?= $dashboardId ?>"><div class="row g-3"><div class="col-md-8"><label class="form-label" for="access-target"><?= __('Perfil ou grupo', 'biforglpi') ?></label><select class="form-select" id="access-target" name="target" required><option value=""><?= __('Selecione', 'biforglpi') ?></option><optgroup label="<?= __('Perfis', 'biforglpi') ?>"><?php foreach ($profiles as $targetId => $name): ?><option value="Profile:<?= $targetId ?>"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></optgroup><optgroup label="<?= __('Grupos', 'biforglpi') ?>"><?php foreach ($groups as $targetId => $name): ?><option value="Group:<?= $targetId ?>"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></optgroup></select></div><div class="col-md-4 d-flex align-items-end"><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="can_edit" value="1"><span class="form-check-label"><?= __('Também pode editar este dashboard', 'biforglpi') ?></span></label></div></div><button class="btn btn-primary mt-4" name="save" value="1"><?= __('Salvar acesso', 'biforglpi') ?></button><?php Html::closeForm(); ?></div></section></main>
<?php Html::footer(); ?>
