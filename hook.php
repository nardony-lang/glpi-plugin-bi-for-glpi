<?php

function plugin_biforglpi_install(): bool
{
    require_once __DIR__ . '/install/install.php';
    return plugin_biforglpi_run_install();
}

function plugin_biforglpi_uninstall(): bool
{
    require_once __DIR__ . '/install/install.php';
    return plugin_biforglpi_run_uninstall();
}
