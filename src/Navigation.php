<?php

namespace GlpiPlugin\Biforglpi;

use Plugin;
use Session;

final class Navigation
{
    public static function render(string $active): void
    {
        $baseUrl = htmlspecialchars(Plugin::getWebDir('biforglpi'), ENT_QUOTES, 'UTF-8');
        $links = [];
        if (Session::haveRight(Profile::RIGHT_VIEW_DASHBOARD, READ)) {
            $links['dashboard'] = ['Dashboard', $baseUrl . '/front/dashboard.php', 'ti-layout-dashboard'];
            $links['dashboards'] = ['Meus dashboards', $baseUrl . '/front/dashboards.php', 'ti-folders'];
        }
        if (Session::haveRight(Profile::RIGHT_MANAGE_QUERIES, READ)) {
            $links['queries'] = ['Consultas salvas', $baseUrl . '/front/queries.php', 'ti-database'];
        }
        if (Session::haveRight(SqlLab::RIGHT_NAME, READ)) {
            $links['lab'] = ['Laboratório SQL', $baseUrl . '/front/sqllab.php', 'ti-code'];
        }

        echo '<nav class="biforglpi-nav" aria-label="BI for GLPI">';
        foreach ($links as $key => [$label, $url, $icon]) {
            $class = $key === $active ? 'btn btn-primary' : 'btn btn-outline-secondary';
            echo '<a class="' . $class . '" href="' . $url . '">';
            echo '<i class="ti ' . $icon . '" aria-hidden="true"></i> ';
            echo htmlspecialchars(__($label, 'biforglpi'), ENT_QUOTES, 'UTF-8');
            echo '</a>';
        }
        echo '</nav>';
    }
}
