<?php

namespace GlpiPlugin\Biforglpi;

use CommonGLPI;
use Html;
use ProfileRight;
use Session;

final class Profile extends \Profile
{
    public static $rightname = 'profile';

    public static function getTypeName($nb = 0): string
    {
        return __('BI for GLPI', 'biforglpi');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof \Profile && (int) $item->getField('id') > 0) {
            return self::createTabEntry(self::getTypeName());
        }

        return '';
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ): bool {
        if ($item instanceof \Profile && $item->getID() > 0) {
            (new self())->showPluginRights((int) $item->getID());
        }

        return true;
    }

    /** @return list<array<string, mixed>> */
    public static function getAllRights(): array
    {
        return [[
            'rights' => [READ => __('Executar consultas SQL', 'biforglpi')],
            'label'  => __('Laboratório SQL', 'biforglpi'),
            'field'  => SqlLab::RIGHT_NAME,
        ]];
    }

    public function showPluginRights(int $profilesId): void
    {
        $profile = new \Profile();
        if (!$profile->getFromDB($profilesId)) {
            return;
        }

        $canEdit = Session::haveRight(self::$rightname, UPDATE);
        echo "<div class='firstbloc'>";
        if ($canEdit) {
            echo "<form method='post' action='"
                . htmlspecialchars($profile->getFormURL(), ENT_QUOTES, 'UTF-8')
                . "'>";
        }

        $profile->displayRightsChoiceMatrix(self::getAllRights(), [
            'canedit'       => $canEdit,
            'default_class' => 'tab_bg_2',
            'title'         => self::getTypeName(),
        ]);

        if ($canEdit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profilesId]);
            echo Html::submit(_sx('button', 'Save'), [
                'name'  => 'update',
                'class' => 'btn btn-primary',
            ]);
            echo '</div>';
            Html::closeForm();
        }

        echo '</div>';
    }

    public static function installRights(): bool
    {
        $rightWasCreated = false;
        if (countElementsInTable('glpi_profilerights', ['name' => SqlLab::RIGHT_NAME]) === 0) {
            if (!ProfileRight::addProfileRights([SqlLab::RIGHT_NAME])) {
                return false;
            }
            $rightWasCreated = true;
        }

        if (!$rightWasCreated || !isset($_SESSION['glpiactiveprofile']['id'])) {
            return true;
        }

        $profilesId = (int) $_SESSION['glpiactiveprofile']['id'];
        $profile = new \Profile();
        if (!$profile->getFromDB($profilesId)) {
            return false;
        }

        if (!$profile->update([
            'id' => $profilesId,
            '_' . SqlLab::RIGHT_NAME => [READ => 1],
        ])) {
            return false;
        }

        $_SESSION['glpiactiveprofile'][SqlLab::RIGHT_NAME] = READ;
        return true;
    }

    public static function uninstallRights(): bool
    {
        unset($_SESSION['glpiactiveprofile'][SqlLab::RIGHT_NAME]);
        return ProfileRight::deleteProfileRights([SqlLab::RIGHT_NAME]);
    }
}
