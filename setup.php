<?php

/**
 * BI for GLPI plugin bootstrap.
 *
 * @license GPL-3.0-or-later
 */

use Glpi\Plugin\Hooks;
use GlpiPlugin\Biforglpi\Profile as BiforglpiProfile;
use GlpiPlugin\Biforglpi\SqlLab;

define('PLUGIN_BIFORGLPI_VERSION', '0.3.0-rc5');
define('PLUGIN_BIFORGLPI_MIN_GLPI_VERSION', '11.0.0');
define('PLUGIN_BIFORGLPI_MAX_GLPI_VERSION', '12.0.0');

function plugin_init_biforglpi(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::MENU_TOADD]['biforglpi'] = [
        'tools' => SqlLab::class,
    ];

    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (is_string($requestPath) && str_contains($requestPath, '/biforglpi/front/')) {
        $PLUGIN_HOOKS[Hooks::ADD_CSS]['biforglpi'][] = 'css/sqllab.css';
    }
    if (is_string($requestPath) && str_contains($requestPath, '/biforglpi/front/sqllab.php')) {
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['biforglpi'][] = 'js/sqllab.js';
    }
    if (is_string($requestPath) && str_contains($requestPath, '/biforglpi/front/dashboard.php')) {
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['biforglpi'][] = 'js/dashboard.js';
    }

    Plugin::registerClass(BiforglpiProfile::class, [
        'addtabon' => Profile::class,
    ]);
}

/** @return array<string, mixed> */
function plugin_version_biforglpi(): array
{
    return [
        'name'         => 'BI for GLPI',
        'version'      => PLUGIN_BIFORGLPI_VERSION,
        'author'       => 'Douglas Nardoni da Silva',
        'license'      => 'GPL-3.0-or-later',
        'homepage'     => 'https://github.com/nardony-lang/glpi-plugin-bi-for-glpi',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_BIFORGLPI_MIN_GLPI_VERSION,
                'max' => PLUGIN_BIFORGLPI_MAX_GLPI_VERSION,
            ],
            'php' => ['min' => '8.2'],
        ],
    ];
}

function plugin_biforglpi_check_prerequisites(): bool
{
    if (version_compare(GLPI_VERSION, PLUGIN_BIFORGLPI_MIN_GLPI_VERSION, '<')) {
        echo sprintf('BI for GLPI requires GLPI %s or newer.', PLUGIN_BIFORGLPI_MIN_GLPI_VERSION);
        return false;
    }

    if (version_compare(GLPI_VERSION, PLUGIN_BIFORGLPI_MAX_GLPI_VERSION, '>=')) {
        echo sprintf('BI for GLPI is not yet compatible with GLPI %s or newer.', PLUGIN_BIFORGLPI_MAX_GLPI_VERSION);
        return false;
    }

    if (version_compare(PHP_VERSION, '8.2.0', '<')) {
        echo 'BI for GLPI requires PHP 8.2 or newer.';
        return false;
    }

    return true;
}

function plugin_biforglpi_check_config(bool $verbose = false): bool
{
    return true;
}
