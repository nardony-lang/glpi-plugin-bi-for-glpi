<?php

namespace Glpi\Plugin {
    final class Hooks
    {
        public const MENU_TOADD = 'menu_toadd';
        public const ADD_CSS = 'add_css';
        public const ADD_JAVASCRIPT = 'add_javascript';
    }
}

namespace GlpiPlugin\Biforglpi {
    final class Profile {}
    final class SqlLab {}
}

namespace {
    final class Profile {}

    final class Plugin
    {
        public static function registerClass(string $class, array $options): void {}
    }

    $_SERVER['REQUEST_URI'] = '/plugins/biforglpi/front/sqllab.php';
    $PLUGIN_HOOKS = [];

    require_once __DIR__ . '/../setup.php';
    plugin_init_biforglpi();

    assertSameValue(
        'Hook CSS do GLPI 11',
        ['css/sqllab.css'],
        $PLUGIN_HOOKS['add_css']['biforglpi'] ?? null
    );
    assertSameValue(
        'Hook JavaScript do GLPI 11',
        ['js/sqllab.js'],
        $PLUGIN_HOOKS['add_javascript']['biforglpi'] ?? null
    );
}
