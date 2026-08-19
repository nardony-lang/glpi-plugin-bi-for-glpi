<?php

use GlpiPlugin\Biforglpi\Profile;
use GlpiPlugin\Biforglpi\SavedQuery;

function plugin_biforglpi_run_install(): bool
{
    global $DB;

    $migration = new Migration(PLUGIN_BIFORGLPI_VERSION);
    if (!$DB->tableExists(SavedQuery::TABLE)) {
        $charset = DBConnection::getDefaultCharset();
        $collation = DBConnection::getDefaultCollation();
        $keySign = DBConnection::getDefaultPrimaryKeySignOption();
        $table = SavedQuery::TABLE;

        $DB->doQuery("CREATE TABLE `{$table}` (
            `id` INT {$keySign} NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `query_sql` LONGTEXT NOT NULL,
            `visualization` VARCHAR(20) NOT NULL DEFAULT 'number',
            `row_limit` INT UNSIGNED NOT NULL DEFAULT 100,
            `is_active` TINYINT NOT NULL DEFAULT 1,
            `users_id` INT {$keySign} NOT NULL DEFAULT 0,
            `date_creation` TIMESTAMP NULL DEFAULT NULL,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `is_active` (`is_active`),
            KEY `visualization` (`visualization`),
            KEY `users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC");
    }

    $migration->executeMigration();
    return Profile::installRights();
}

function plugin_biforglpi_run_uninstall(): bool
{
    global $DB;

    if ($DB->tableExists(SavedQuery::TABLE)) {
        $DB->doQuery('DROP TABLE `' . SavedQuery::TABLE . '`');
    }

    return Profile::uninstallRights();
}
