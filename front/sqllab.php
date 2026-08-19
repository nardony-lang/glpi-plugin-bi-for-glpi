<?php

use GlpiPlugin\Biforglpi\Navigation;
use GlpiPlugin\Biforglpi\DashboardFilter;
use GlpiPlugin\Biforglpi\Profile;
use GlpiPlugin\Biforglpi\SavedQuery;
use GlpiPlugin\Biforglpi\SqlExecutor;
use GlpiPlugin\Biforglpi\SqlLab;

include '../../../inc/includes.php';

Session::checkRight(SqlLab::RIGHT_NAME, READ);

$pluginUrl = Plugin::getWebDir('biforglpi');
$escapedPluginUrl = htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8');
$initialSql = "SELECT id, name, date_mod\nFROM glpi_computers\nORDER BY date_mod DESC";
$initialLimit = SqlExecutor::DEFAULT_LIMIT;
$initialQueryName = null;
$filterContext = DashboardFilter::context($_GET);
$filterEntities = DashboardFilter::entities();

$savedQueryId = filter_input(INPUT_GET, 'saved_query_id', FILTER_VALIDATE_INT);
if ($savedQueryId && Session::haveRight(Profile::RIGHT_MANAGE_QUERIES, READ)) {
    $savedQuery = SavedQuery::find((int) $savedQueryId);
    if ($savedQuery !== null) {
        $initialSql = (string) $savedQuery['query_sql'];
        $initialLimit = (int) $savedQuery['row_limit'];
        $initialQueryName = (string) $savedQuery['name'];
    }
}

Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'tools', SqlLab::class);
?>
<main class="biforglpi-lab container-xl" data-endpoint="<?= $escapedPluginUrl ?>/ajax/execute.php">
    <?php Navigation::render('lab'); ?>

    <section class="card biforglpi-card">
        <div class="card-header">
            <div>
                <h1 class="card-title mb-1"><?= __('Laboratório SQL', 'biforglpi') ?></h1>
                <p class="text-secondary mb-0">
                    <?= __('Execute consultas de leitura e inspecione os resultados sem sair do GLPI.', 'biforglpi') ?>
                </p>
            </div>
            <span class="badge bg-green-lt"><?= __('Somente leitura', 'biforglpi') ?></span>
        </div>

        <div class="card-body">
            <?php if ($initialQueryName !== null): ?>
                <div class="alert alert-success" role="status">
                    <?= sprintf(
                        __('Consulta salva carregada: %s', 'biforglpi'),
                        htmlspecialchars($initialQueryName, ENT_QUOTES, 'UTF-8')
                    ) ?>
                </div>
            <?php endif; ?>
            <div class="alert alert-info" role="status">
                <?= sprintf(
                    __('Permitidos: SELECT, WITH e EXPLAIN. Escritas, comentários e múltiplas instruções são bloqueados. Tempo máximo: %d segundos.', 'biforglpi'),
                    SqlExecutor::MAX_EXECUTION_TIME_SECONDS
                ) ?>
            </div>

            <form id="biforglpi-query-form">
                <input type="hidden" name="_glpi_csrf_token" id="biforglpi-csrf-token" value="">

                <label class="form-label" for="biforglpi-sql"><?= __('Consulta SQL', 'biforglpi') ?></label>
                <textarea
                    class="form-control font-monospace"
                    id="biforglpi-sql"
                    name="sql"
                    rows="10"
                    spellcheck="false"
                    required
                ><?= htmlspecialchars($initialSql, ENT_QUOTES, 'UTF-8') ?></textarea>

                <div class="row g-3 mt-1">
                    <div class="col-lg-4">
                        <label class="form-label" for="biforglpi-entity"><?= __('Entidade usada nas variáveis', 'biforglpi') ?></label>
                        <select class="form-select" id="biforglpi-entity" name="entity_id">
                            <?php foreach ($filterEntities as $entityId => $entityName): ?>
                                <option value="<?= $entityId ?>" <?= $entityId === $filterContext['entity_id'] ? 'selected' : '' ?>><?= htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label" for="biforglpi-date-start"><?= __('Data inicial', 'biforglpi') ?></label>
                        <input class="form-control" id="biforglpi-date-start" name="date_start" type="date" value="<?= htmlspecialchars($filterContext['date_start'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label" for="biforglpi-date-end"><?= __('Data final', 'biforglpi') ?></label>
                        <input class="form-control" id="biforglpi-date-end" name="date_end" type="date" value="<?= htmlspecialchars($filterContext['date_end'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="biforglpi-actions mt-3">
                    <div>
                        <label class="form-label" for="biforglpi-limit"><?= __('Limite de linhas', 'biforglpi') ?></label>
                        <input
                            class="form-control"
                            id="biforglpi-limit"
                            name="limit"
                            type="number"
                            min="1"
                            max="<?= SqlExecutor::MAX_LIMIT ?>"
                            value="<?= $initialLimit ?>"
                        >
                    </div>
                    <button class="btn btn-primary align-self-end" id="biforglpi-run" type="submit">
                        <i class="ti ti-player-play" aria-hidden="true"></i>
                        <?= __('Executar consulta', 'biforglpi') ?>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="card biforglpi-card mt-4" id="biforglpi-result-card" hidden>
        <div class="card-header">
            <h2 class="card-title"><?= __('Resultados', 'biforglpi') ?></h2>
            <div class="text-secondary" id="biforglpi-summary" aria-live="polite"></div>
        </div>
        <div class="card-body p-0">
            <div class="alert alert-danger m-3" id="biforglpi-error" role="alert" hidden></div>
            <div class="table-responsive" id="biforglpi-table-wrap"></div>
        </div>
    </section>
</main>
<?php
Html::footer();
