<?php

use GlpiPlugin\Biforglpi\Navigation;
use GlpiPlugin\Biforglpi\Profile as BiforglpiProfile;
use GlpiPlugin\Biforglpi\SavedQuery;
use GlpiPlugin\Biforglpi\SqlLab;

include '../../../inc/includes.php';

Session::checkRight(BiforglpiProfile::RIGHT_MANAGE_QUERIES, READ);

$pluginUrl = Plugin::getWebDir('biforglpi');
$escapedPluginUrl = htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8');
$queries = SavedQuery::all();
$canManage = Session::haveRight(BiforglpiProfile::RIGHT_MANAGE_QUERIES, UPDATE);

Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'plugins', SqlLab::class);
?>
<main class="biforglpi-lab container-xl">
    <?php Navigation::render('queries'); ?>

    <div class="biforglpi-page-heading">
        <div>
            <h1><?= __('Consultas salvas', 'biforglpi') ?></h1>
            <p class="text-secondary mb-0">
                <?= __('Consultas reutilizáveis para indicadores e tabelas.', 'biforglpi') ?>
            </p>
        </div>
        <?php if ($canManage): ?>
            <a class="btn btn-primary" href="<?= $escapedPluginUrl ?>/front/savedquery.form.php">
                <i class="ti ti-plus" aria-hidden="true"></i>
                <?= __('Nova consulta', 'biforglpi') ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success" role="status"><?= __('Consulta salva com sucesso.', 'biforglpi') ?></div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success" role="status"><?= __('Consulta excluída com sucesso.', 'biforglpi') ?></div>
    <?php endif; ?>

    <section class="card biforglpi-card">
        <?php if ($queries === []): ?>
            <div class="card-body biforglpi-empty">
                <i class="ti ti-database-off" aria-hidden="true"></i>
                <h2><?= __('Nenhuma consulta salva', 'biforglpi') ?></h2>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-striped mb-0">
                    <thead>
                        <tr>
                            <th><?= __('Nome', 'biforglpi') ?></th>
                            <th><?= __('Visualização', 'biforglpi') ?></th>
                            <th><?= __('Limite', 'biforglpi') ?></th>
                            <th><?= __('Status', 'biforglpi') ?></th>
                            <th class="text-end"><?= __('Ações', 'biforglpi') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queries as $query): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars((string) $query['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if (!empty($query['description'])): ?>
                                        <div class="small text-secondary">
                                            <?= htmlspecialchars((string) $query['description'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $query['visualization'] === SavedQuery::TYPE_NUMBER
                                        ? __('Indicador', 'biforglpi')
                                        : __('Tabela', 'biforglpi') ?>
                                </td>
                                <td><?= (int) $query['row_limit'] ?></td>
                                <td>
                                    <span class="badge <?= $query['is_active'] ? 'bg-green-lt' : 'bg-secondary-lt' ?>">
                                        <?= $query['is_active'] ? __('Ativa', 'biforglpi') : __('Inativa', 'biforglpi') ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if (Session::haveRight(SqlLab::RIGHT_NAME, READ)): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= $escapedPluginUrl ?>/front/sqllab.php?saved_query_id=<?= (int) $query['id'] ?>">
                                            <?= __('Executar', 'biforglpi') ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($canManage): ?>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/savedquery.form.php?id=<?= (int) $query['id'] ?>">
                                            <?= __('Editar', 'biforglpi') ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php
Html::footer();
