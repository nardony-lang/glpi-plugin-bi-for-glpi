<?php

use GlpiPlugin\Biforglpi\SqlExecutor;
use GlpiPlugin\Biforglpi\SqlLab;

include '../../../inc/includes.php';

Session::checkRight(SqlLab::RIGHT_NAME, READ);

$pluginUrl = Plugin::getWebDir('biforglpi');
$escapedPluginUrl = htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8');

Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'plugins', SqlLab::class);
?>
<main class="biforglpi-lab container-xl" data-endpoint="<?= $escapedPluginUrl ?>/ajax/execute.php">
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
                >SELECT id, name, date_mod
FROM glpi_computers
ORDER BY date_mod DESC</textarea>

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
                            value="<?= SqlExecutor::DEFAULT_LIMIT ?>"
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
