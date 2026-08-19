<?php

namespace GlpiPlugin\Biforglpi;

use CommonGLPI;
use Html;
use ProfileRight;
use Session;

final class Profile extends \Profile
{
    public const RIGHT_VIEW_DASHBOARD = 'plugin_biforglpi_dashboard';
    public const RIGHT_MANAGE_DASHBOARDS = 'plugin_biforglpi_dashboard_manage';
    public const RIGHT_MANAGE_QUERIES = 'plugin_biforglpi_queries';

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
        return [
            [
                'rights' => [READ => __('Visualizar dashboards', 'biforglpi')],
                'label'  => __('Dashboards', 'biforglpi'),
                'field'  => self::RIGHT_VIEW_DASHBOARD,
            ],
            [
                'rights' => [UPDATE => __('Criar e administrar dashboards', 'biforglpi')],
                'label'  => __('Administração de dashboards', 'biforglpi'),
                'field'  => self::RIGHT_MANAGE_DASHBOARDS,
            ],
            [
                'rights' => [
                    READ   => __('Visualizar consultas salvas', 'biforglpi'),
                    UPDATE => __('Gerenciar consultas salvas', 'biforglpi'),
                ],
                'label' => __('Consultas salvas', 'biforglpi'),
                'field' => self::RIGHT_MANAGE_QUERIES,
            ],
            [
                'rights' => [READ => __('Executar consultas SQL', 'biforglpi')],
                'label'  => __('Laboratório SQL', 'biforglpi'),
                'field'  => SqlLab::RIGHT_NAME,
            ],
        ];
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
        $createdRights = [];
        foreach (self::getAllRights() as $definition) {
            $field = (string) $definition['field'];
            if (countElementsInTable('glpi_profilerights', ['name' => $field]) > 0) {
                continue;
            }

            if (!ProfileRight::addProfileRights([$field])) {
                return false;
            }
            $createdRights[$field] = array_keys($definition['rights']);
        }

        if ($createdRights === [] || !isset($_SESSION['glpiactiveprofile']['id'])) {
            return true;
        }

        $profilesId = (int) $_SESSION['glpiactiveprofile']['id'];
        $profile = new \Profile();
        if (!$profile->getFromDB($profilesId)) {
            return false;
        }

        $input = ['id' => $profilesId];
        foreach ($createdRights as $field => $rights) {
            $input['_' . $field] = array_fill_keys($rights, 1);
        }

        if (!$profile->update($input)) {
            return false;
        }

        foreach ($createdRights as $field => $rights) {
            $_SESSION['glpiactiveprofile'][$field] = array_reduce(
                $rights,
                static fn(int $value, int $right): int => $value | $right,
                0
            );
        }
        return true;
    }

    public static function uninstallRights(): bool
    {
        $fields = array_map(
            static fn(array $definition): string => (string) $definition['field'],
            self::getAllRights()
        );
        foreach ($fields as $field) {
            unset($_SESSION['glpiactiveprofile'][$field]);
        }

        return ProfileRight::deleteProfileRights($fields);
    }
}
