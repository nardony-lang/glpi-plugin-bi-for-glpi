<?php

use GlpiPlugin\Biforglpi\Dashboard;
use GlpiPlugin\Biforglpi\DashboardAccess;
use GlpiPlugin\Biforglpi\Navigation;
use GlpiPlugin\Biforglpi\Profile as BiforglpiProfile;
use GlpiPlugin\Biforglpi\SqlLab;

include '../../../inc/includes.php';
Session::checkRight(BiforglpiProfile::RIGHT_VIEW_DASHBOARD, READ);

$pluginUrl = Plugin::getWebDir('biforglpi');
$escapedPluginUrl = htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8');
$dashboards = Dashboard::accessible();
$canCreate = Session::haveRight(BiforglpiProfile::RIGHT_MANAGE_DASHBOARDS, UPDATE);

Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'tools', SqlLab::class);
?>
<main class="biforglpi-lab container-xl">
    <?php Navigation::render('dashboards'); ?>
    <div class="biforglpi-page-heading">
        <div><h1><?= __('Meus dashboards', 'biforglpi') ?></h1><p class="text-secondary mb-0"><?= __('Painéis disponíveis para seu perfil ou grupo.', 'biforglpi') ?></p></div>
        <?php if ($canCreate): ?><a class="btn btn-primary" href="<?= $escapedPluginUrl ?>/front/dashboard.form.php"><i class="ti ti-plus"></i> <?= __('Novo dashboard', 'biforglpi') ?></a><?php endif; ?>
    </div>
    <?php if (isset($_GET['saved'])): ?><div class="alert alert-success"><?= __('Dashboard salvo com sucesso.', 'biforglpi') ?></div><?php endif; ?>
    <section class="biforglpi-dashboard-list">
        <?php foreach ($dashboards as $dashboard): ?>
            <article class="card biforglpi-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-3"><div><h2 class="h3 mb-1"><?= htmlspecialchars((string) $dashboard['name'], ENT_QUOTES, 'UTF-8') ?></h2><p class="text-secondary mb-2"><?= htmlspecialchars((string) ($dashboard['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p></div><?php if ($dashboard['is_demo']): ?><span class="badge bg-azure-lt align-self-start"><?= __('Demonstração', 'biforglpi') ?></span><?php endif; ?></div>
                    <div class="d-flex gap-2"><a class="btn btn-primary" href="<?= $escapedPluginUrl ?>/front/dashboard.php?id=<?= (int) $dashboard['id'] ?>"><?= __('Abrir', 'biforglpi') ?></a><?php if (DashboardAccess::canEdit((int) $dashboard['id'])): ?><a class="btn btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/dashboard.form.php?id=<?= (int) $dashboard['id'] ?>"><?= __('Configurar', 'biforglpi') ?></a><?php endif; ?></div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if ($dashboards === []): ?><section class="card biforglpi-card"><div class="card-body biforglpi-empty"><i class="ti ti-layout-dashboard-off"></i><h2><?= __('Nenhum dashboard disponível', 'biforglpi') ?></h2><p class="text-secondary"><?= __('Solicite acesso a um dashboard ou crie o primeiro painel.', 'biforglpi') ?></p></div></section><?php endif; ?>
    </section>
</main>
<?php Html::footer(); ?>
