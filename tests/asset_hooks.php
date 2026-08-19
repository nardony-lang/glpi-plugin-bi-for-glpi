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
    final class Profile
    {
        public static function installRights(): bool
        {
            return true;
        }

        public static function uninstallRights(): bool
        {
            return true;
        }
    }
    final class SqlLab {}
}

namespace {
    final class Profile {}

    final class Plugin
    {
        public static function registerClass(string $class, array $options): void {}
    }

    final class DBConnection
    {
        public static function getDefaultCharset(): string
        {
            return 'utf8mb4';
        }

        public static function getDefaultCollation(): string
        {
            return 'utf8mb4_unicode_ci';
        }

        public static function getDefaultPrimaryKeySignOption(): string
        {
            return 'UNSIGNED';
        }
    }

    final class Migration
    {
        public function __construct(string $version) {}

        public function executeMigration(): void {}
    }

    final class BiforglpiTestDb
    {
        /** @var list<string> */
        public array $queries = [];

        public function tableExists(string $table): bool
        {
            return false;
        }

        public function doQuery(string $query): bool
        {
            $this->queries[] = $query;
            return true;
        }
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

    $DB = new BiforglpiTestDb();
    require_once __DIR__ . '/../install/install.php';
    assertSameValue('Instalação da tabela de consultas', true, plugin_biforglpi_run_install());
    assertSameValue(
        'Tabela de consultas salvas no esquema',
        true,
        str_contains($DB->queries[0] ?? '', 'glpi_plugin_biforglpi_savedqueries')
    );
    assertSameValue(
        'SQL armazenado como LONGTEXT',
        true,
        str_contains($DB->queries[0] ?? '', '`query_sql` LONGTEXT NOT NULL')
    );
}
