<?php

namespace GlpiPlugin\Biforglpi;

use CommonGLPI;
use Session;

final class SqlLab extends CommonGLPI
{
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

        return [
            'title' => self::getMenuName(),
            'page'  => '/plugins/biforglpi/front/sqllab.php',
            'icon'  => 'ti ti-chart-bar',
            'links' => ['search' => '/plugins/biforglpi/front/sqllab.php'],
        ];
    }

    public static function canView(): bool
    {
        return Session::haveRight('config', READ);
    }
}
