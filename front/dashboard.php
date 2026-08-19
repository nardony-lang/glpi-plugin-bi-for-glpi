<?php

use GlpiPlugin\Biforglpi\Navigation;
use GlpiPlugin\Biforglpi\Profile as BiforglpiProfile;
use GlpiPlugin\Biforglpi\SavedQuery;
use GlpiPlugin\Biforglpi\SqlExecutor;
use GlpiPlugin\Biforglpi\SqlLab;

include '../../../inc/includes.php';

Session::checkRight(BiforglpiProfile::RIGHT_VIEW_DASHBOARD, READ);

$pluginUrl = Plugin::getWebDir('biforglpi');
$escapedPluginUrl = htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8');
$savedQueries = SavedQuery::all(true);
$indicators = array_slice(array_values(array_filter(
    $savedQueries,
    static fn(array $query): bool => $query['visualization'] === SavedQuery::TYPE_NUMBER
)), 0, 12);
$tables = array_values(array_filter(
    $savedQueries,
    static fn(array $query): bool => $query['visualization'] === SavedQuery::TYPE_TABLE
));

$indicatorResults = [];
foreach ($indicators as $query) {
    try {
        $result = (new SqlExecutor())->execute((string) $query['query_sql'], 1);
        $firstRow = $result['rows'][0] ?? [];
        $value = $firstRow !== [] ? reset($firstRow) : 0;
        if (is_numeric($value)) {
            $number = (float) $value;
            $value = floor($number) === $number
                ? number_format($number, 0, ',', '.')
                : number_format($number, 2, ',', '.');
        }
        $indicatorResults[(int) $query['id']] = [
            'ok' => true,
            'value' => (string) $value,
            'elapsed_ms' => $result['elapsed_ms'],
        ];
    } catch (Throwable) {
        $indicatorResults[(int) $query['id']] = [
            'ok' => false,
            'message' => __('Indicador indisponível. Revise a consulta salva.', 'biforglpi'),
        ];
    }
}

Html::header(__('BI for GLPI', 'biforglpi'), $_SERVER['PHP_SELF'], 'plugins', SqlLab::class);
?>
<main class="biforglpi-lab container-xl">
    <?php Navigation::render('dashboard'); ?>

    <div class="biforglpi-page-heading">
        <div>
            <h1><?= __('Dashboard', 'biforglpi') ?></h1>
            <p class="text-secondary mb-0">
                <?= __('Indicadores gerados a partir das consultas salvas e ativas.', 'biforglpi') ?>
            </p>
        </div>
        <?php if (Session::haveRight(BiforglpiProfile::RIGHT_MANAGE_QUERIES, UPDATE)): ?>
            <a class="btn btn-primary" href="<?= $escapedPluginUrl ?>/front/savedquery.form.php">
                <i class="ti ti-plus" aria-hidden="true"></i>
                <?= __('Nova consulta', 'biforglpi') ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($indicators === [] && $tables === []): ?>
        <section class="card biforglpi-card">
            <div class="card-body biforglpi-empty">
                <i class="ti ti-chart-bar-off" aria-hidden="true"></i>
                <h2><?= __('Nenhum indicador configurado', 'biforglpi') ?></h2>
                <p class="text-secondary">
                    <?= __('Crie uma consulta salva e marque-a como ativa para começar.', 'biforglpi') ?>
                </p>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($indicators !== []): ?>
        <section class="biforglpi-indicator-grid" aria-label="<?= __('Indicadores', 'biforglpi') ?>">
            <?php foreach ($indicators as $query): ?>
                <?php $indicator = $indicatorResults[(int) $query['id']]; ?>
                <article class="card biforglpi-card biforglpi-indicator">
                    <div class="card-body">
                        <div class="text-secondary biforglpi-indicator-title">
                            <?= htmlspecialchars((string) $query['name'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php if ($indicator['ok']): ?>
                            <div class="biforglpi-indicator-value">
                                <?= htmlspecialchars($indicator['value'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="small text-secondary">
                                <?= sprintf(__('%s ms', 'biforglpi'), $indicator['elapsed_ms']) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger mt-3 mb-0" role="alert">
                                <?= htmlspecialchars($indicator['message'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($query['description'])): ?>
                            <p class="text-secondary mt-3 mb-0">
                                <?= nl2br(htmlspecialchars((string) $query['description'], ENT_QUOTES, 'UTF-8')) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($tables !== []): ?>
        <section class="card biforglpi-card mt-4">
            <div class="card-header">
                <h2 class="card-title"><?= __('Consultas em tabela', 'biforglpi') ?></h2>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($tables as $query): ?>
                    <div class="list-group-item biforglpi-query-link">
                        <div>
                            <strong><?= htmlspecialchars((string) $query['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($query['description'])): ?>
                                <div class="text-secondary">
                                    <?= htmlspecialchars((string) $query['description'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (Session::haveRight(SqlLab::RIGHT_NAME, READ)): ?>
                            <a class="btn btn-outline-primary" href="<?= $escapedPluginUrl ?>/front/sqllab.php?saved_query_id=<?= (int) $query['id'] ?>">
                                <?= __('Abrir no laboratório', 'biforglpi') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php
Html::footer();
