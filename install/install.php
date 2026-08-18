<?php

use GlpiPlugin\Biforglpi\Profile;

function plugin_biforglpi_run_install(): bool
{
    return Profile::installRights();
}

function plugin_biforglpi_run_uninstall(): bool
{
    return Profile::uninstallRights();
}
