<?php

use GlpiPlugin\Biforglpi\Navigation;
use GlpiPlugin\Biforglpi\Profile as BiforglpiProfile;
use GlpiPlugin\Biforglpi\SavedQuery;
use GlpiPlugin\Biforglpi\SqlExecutor;
use GlpiPlugin\Biforglpi\SqlLab;

include '../../../inc/includes.php';

Session::checkRight(BiforglpiProfile::RIGHT_MANAGE_QUERIES, UPDATE);

$pluginUrl = Plugin::getWebDir('biforglpi');
$escapedPluginUrl = htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8');
$id = filter_var($_POST['id'] ?? $_GET['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$error = null;
$redirectUrl = null;
$query = [
    'id'            => 0,
    'name'          => '',
    'description'   => '',
    'query_sql'     => "SELECT COUNT(*) AS total\nFROM glpi_tickets\nWHERE is_deleted = 0",
    'visualization' => SavedQuery::TYPE_NUMBER,
    'row_limit'     => SqlExecutor::DEFAULT_LIMIT,
    'is_active'     => 1,
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        if (isset($_POST['delete']) && $id > 0) {
            SavedQuery::delete($id);
            $redirectUrl = $pluginUrl . '/front/queries.php?deleted=1';
        } elseif (isset($_POST['save'])) {
            if ($id > 0) {
                SavedQuery::update($id, $_POST);
            } else {
                SavedQuery::create($_POST);
            }
            $redirectUrl = $pluginUrl . '/front/queries.php?saved=1';
        }
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
        $query = array_merge($query, $_POST, ['id' => $id]);
    } catch (Throwable) {
        $error = __('Não foi possível concluir a operação. Tente novamente ou consulte os logs do GLPI.', 'biforglpi');
        $query = array_merge($query, $_POST, ['id' => $id]);
    }

    if ($redirectUrl !== null) {
        Html::redirect($redirectUrl);
    }
} elseif ($id > 0) {
    $storedQuery = SavedQuery::find($id);
    if ($storedQuery === null) {
        http_response_code(404);
        $error = __('Consulta salva não encontrada.', 'biforglpi');
    } else {
        $query = $storedQuery;
    }
}

Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'plugins', SqlLab::class);
?>
<main class="biforglpi-lab container-xl">
    <?php Navigation::render('queries'); ?>

    <div class="biforglpi-page-heading">
        <div>
            <h1><?= $id > 0 ? __('Editar consulta', 'biforglpi') : __('Nova consulta', 'biforglpi') ?></h1>
            <p class="text-secondary mb-0">
                <?= __('Salve uma consulta segura para reutilizá-la no dashboard.', 'biforglpi') ?>
            </p>
        </div>
    </div>

    <?php if ($error !== null): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="card biforglpi-card">
        <div class="card-body">
            <form method="post" action="<?= $escapedPluginUrl ?>/front/savedquery.form.php">
                <input type="hidden" name="id" value="<?= (int) $id ?>">

                <div class="mb-3">
                    <label class="form-label" for="biforglpi-query-name"><?= __('Nome', 'biforglpi') ?></label>
                    <input class="form-control" id="biforglpi-query-name" name="name" maxlength="255" required value="<?= htmlspecialchars((string) $query['name'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="biforglpi-query-description"><?= __('Descrição', 'biforglpi') ?></label>
                    <textarea class="form-control" id="biforglpi-query-description" name="description" maxlength="2000" rows="3"><?= htmlspecialchars((string) ($query['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="biforglpi-query-sql"><?= __('Consulta SQL', 'biforglpi') ?></label>
                    <textarea class="form-control font-monospace" id="biforglpi-query-sql" name="query_sql" rows="12" spellcheck="false" required><?= htmlspecialchars((string) $query['query_sql'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    <div class="form-hint"><?= __('Somente SELECT, WITH e EXPLAIN são aceitos.', 'biforglpi') ?></div>
                </div>

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label" for="biforglpi-query-visualization"><?= __('Visualização', 'biforglpi') ?></label>
                        <select class="form-select" id="biforglpi-query-visualization" name="visualization">
                            <option value="number" <?= $query['visualization'] === SavedQuery::TYPE_NUMBER ? 'selected' : '' ?>>
                                <?= __('Indicador numérico', 'biforglpi') ?>
                            </option>
                            <option value="table" <?= $query['visualization'] === SavedQuery::TYPE_TABLE ? 'selected' : '' ?>>
                                <?= __('Tabela', 'biforglpi') ?>
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="biforglpi-query-limit"><?= __('Limite de linhas', 'biforglpi') ?></label>
                        <input class="form-control" id="biforglpi-query-limit" name="row_limit" type="number" min="1" max="<?= SqlExecutor::MAX_LIMIT ?>" value="<?= (int) $query['row_limit'] ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <label class="form-check mb-2">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" name="is_active" type="checkbox" value="1" <?= !empty($query['is_active']) ? 'checked' : '' ?>>
                            <span class="form-check-label"><?= __('Consulta ativa', 'biforglpi') ?></span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit" name="save" value="1">
                        <i class="ti ti-device-floppy" aria-hidden="true"></i>
                        <?= __('Salvar consulta', 'biforglpi') ?>
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= $escapedPluginUrl ?>/front/queries.php">
                        <?= __('Cancelar', 'biforglpi') ?>
                    </a>
                </div>
                <?php Html::closeForm(); ?>

                <?php if ($id > 0): ?>
                    <form class="mt-4 pt-4 border-top" method="post" action="<?= $escapedPluginUrl ?>/front/savedquery.form.php">
                        <input type="hidden" name="id" value="<?= (int) $id ?>">
                        <button class="btn btn-outline-danger" type="submit" name="delete" value="1">
                            <i class="ti ti-trash" aria-hidden="true"></i>
                            <?= __('Excluir consulta', 'biforglpi') ?>
                        </button>
                        <?php Html::closeForm(); ?>
                <?php endif; ?>
        </div>
    </section>
</main>
<?php
Html::footer();
