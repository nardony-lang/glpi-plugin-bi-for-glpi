<?php

namespace GlpiPlugin\Biforglpi;

use CommonGLPI;
use Session;

final class SqlLab extends CommonGLPI
{
    public const RIGHT_NAME = 'plugin_biforglpi_sql_lab';

    public static $rightname = self::RIGHT_NAME;

    public static function getMenuName($nb = 0): string
    {
        return __('BI for GLPI', 'biforglpi');
    }

    /** @return array<string, mixed> */
    public static function getMenuContent(): array
    {
        if (!self::canView()) {
            return [];
        }

        $page = Session::haveRight(Profile::RIGHT_VIEW_DASHBOARD, READ)
            ? '/plugins/biforglpi/front/dashboard.php'
            : '/plugins/biforglpi/front/sqllab.php';

        return [
            'title' => self::getMenuName(),
            'page'  => $page,
            'icon'  => 'ti ti-chart-bar',
            'links' => ['search' => $page],
        ];
    }

    public static function canView(): bool
    {
        return Session::haveRight(self::RIGHT_NAME, READ)
            || Session::haveRight(Profile::RIGHT_VIEW_DASHBOARD, READ)
            || Session::haveRight(Profile::RIGHT_MANAGE_QUERIES, READ);
    }
}
