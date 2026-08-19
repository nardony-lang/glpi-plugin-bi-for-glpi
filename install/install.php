<?php

use GlpiPlugin\Biforglpi\Dashboard;
use GlpiPlugin\Biforglpi\DashboardAccess;
use GlpiPlugin\Biforglpi\DashboardWidget;
use GlpiPlugin\Biforglpi\Profile;
use GlpiPlugin\Biforglpi\SavedQuery;

function plugin_biforglpi_run_install(): bool
{
    global $DB;
    $migration = new Migration(PLUGIN_BIFORGLPI_VERSION);
    $charset = DBConnection::getDefaultCharset();
    $collation = DBConnection::getDefaultCollation();
    $keySign = DBConnection::getDefaultPrimaryKeySignOption();

    if (!$DB->tableExists(SavedQuery::TABLE)) {
        $table = SavedQuery::TABLE;
        $DB->doQuery("CREATE TABLE `{$table}` (
            `id` INT {$keySign} NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL, `description` TEXT DEFAULT NULL,
            `query_sql` LONGTEXT NOT NULL, `visualization` VARCHAR(20) NOT NULL DEFAULT 'number',
            `row_limit` INT UNSIGNED NOT NULL DEFAULT 100, `is_active` TINYINT NOT NULL DEFAULT 1,
            `users_id` INT {$keySign} NOT NULL DEFAULT 0, `date_creation` TIMESTAMP NULL DEFAULT NULL,
            `date_mod` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`id`), KEY `is_active` (`is_active`),
            KEY `visualization` (`visualization`), KEY `users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC");
    }

    if (!$DB->tableExists(Dashboard::TABLE)) {
        $table = Dashboard::TABLE;
        $DB->doQuery("CREATE TABLE `{$table}` (
            `id` INT {$keySign} NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL, `is_active` TINYINT NOT NULL DEFAULT 1,
            `is_demo` TINYINT NOT NULL DEFAULT 0, `users_id` INT {$keySign} NOT NULL DEFAULT 0,
            `date_creation` TIMESTAMP NULL DEFAULT NULL, `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`), KEY `is_active` (`is_active`), KEY `users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC");
    }

    if (!$DB->tableExists(DashboardWidget::TABLE)) {
        $table = DashboardWidget::TABLE;
        $DB->doQuery("CREATE TABLE `{$table}` (
            `id` INT {$keySign} NOT NULL AUTO_INCREMENT, `dashboards_id` INT {$keySign} NOT NULL,
            `savedqueries_id` INT {$keySign} NOT NULL, `title` VARCHAR(255) DEFAULT NULL,
            `widget_type` VARCHAR(20) NOT NULL DEFAULT 'number', `position` INT UNSIGNED NOT NULL DEFAULT 0,
            `width` TINYINT UNSIGNED NOT NULL DEFAULT 4, `demo_data` LONGTEXT DEFAULT NULL,
            `date_creation` TIMESTAMP NULL DEFAULT NULL, `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`), KEY `dashboards_id` (`dashboards_id`),
            KEY `savedqueries_id` (`savedqueries_id`), KEY `position` (`position`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC");
    }

    if (!$DB->tableExists(DashboardAccess::TABLE)) {
        $table = DashboardAccess::TABLE;
        $DB->doQuery("CREATE TABLE `{$table}` (
            `id` INT {$keySign} NOT NULL AUTO_INCREMENT, `dashboards_id` INT {$keySign} NOT NULL,
            `itemtype` VARCHAR(100) NOT NULL, `items_id` INT {$keySign} NOT NULL,
            `rights` INT UNSIGNED NOT NULL DEFAULT 1, PRIMARY KEY (`id`),
            UNIQUE KEY `dashboard_target` (`dashboards_id`, `itemtype`, `items_id`),
            KEY `target` (`itemtype`, `items_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC");
    }

    $migration->executeMigration();
    if (!Profile::installRights()) {
        return false;
    }
    $hasDashboard = false;
    foreach ($DB->request(['FROM' => Dashboard::TABLE, 'LIMIT' => 1]) as $unused) {
        $hasDashboard = true;
    }
    if (!$hasDashboard) {
        plugin_biforglpi_migrate_legacy_dashboard();
    }
    return true;
}

function plugin_biforglpi_migrate_legacy_dashboard(): void
{
    global $DB;
    $now = date('Y-m-d H:i:s');
    $DB->insert(Dashboard::TABLE, [
        'name' => 'Dashboard principal',
        'description' => 'Dashboard criado automaticamente a partir da versão 0.2.0.',
        'is_active' => 1, 'is_demo' => 0,
        'users_id' => (int) ($_SESSION['glpiID'] ?? 0),
        'date_creation' => $now, 'date_mod' => $now,
    ]);
    $dashboardId = (int) $DB->insertId();
    if ($dashboardId < 1) {
        return;
    }
    $grantedProfiles = [];
    foreach ($DB->request([
        'FROM' => 'glpi_profilerights',
        'WHERE' => ['name' => Profile::RIGHT_VIEW_DASHBOARD],
    ]) as $profileRight) {
        $profileId = (int) $profileRight['profiles_id'];
        if ($profileId < 1 || (((int) $profileRight['rights']) & READ) !== READ) {
            continue;
        }
        $DB->insert(DashboardAccess::TABLE, [
            'dashboards_id' => $dashboardId,
            'itemtype' => DashboardAccess::ITEM_PROFILE,
            'items_id' => $profileId,
            'rights' => READ,
        ]);
        $grantedProfiles[] = $profileId;
    }
    $installerProfileId = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
    if ($installerProfileId > 0 && !in_array($installerProfileId, $grantedProfiles, true)) {
        DashboardAccess::grantInstallerProfile($dashboardId);
    }
    $position = 0;
    foreach ($DB->request(['FROM' => SavedQuery::TABLE, 'WHERE' => ['is_active' => 1], 'ORDER' => ['name ASC']]) as $query) {
        $DB->insert(DashboardWidget::TABLE, [
            'dashboards_id' => $dashboardId, 'savedqueries_id' => (int) $query['id'], 'title' => null,
            'widget_type' => (string) $query['visualization'], 'position' => $position++,
            'width' => $query['visualization'] === SavedQuery::TYPE_TABLE ? 12 : 4, 'demo_data' => null,
            'date_creation' => $now, 'date_mod' => $now,
        ]);
    }
}

function plugin_biforglpi_run_uninstall(): bool
{
    global $DB;
    foreach ([DashboardWidget::TABLE, DashboardAccess::TABLE, Dashboard::TABLE, SavedQuery::TABLE] as $table) {
        if ($DB->tableExists($table)) {
            $DB->doQuery('DROP TABLE `' . $table . '`');
        }
    }
    return Profile::uninstallRights();
}
