<?php

namespace GlpiPlugin\Biforglpi;

use InvalidArgumentException;
use Html;
use RuntimeException;
use Session;

final class DashboardAccess
{
    public const TABLE = 'glpi_plugin_biforglpi_dashboardrights';
    public const ITEM_PROFILE = 'Profile';
    public const ITEM_GROUP = 'Group';

    public static function canView(int $dashboardId): bool
    {
        if (!Session::haveRight(Profile::RIGHT_VIEW_DASHBOARD, READ)) {
            return false;
        }
        if (Session::haveRight(Profile::RIGHT_MANAGE_DASHBOARDS, UPDATE)) {
            return true;
        }
        $dashboard = Dashboard::find($dashboardId);
        if ($dashboard === null || !$dashboard['is_active']) {
            return false;
        }
        if ((int) $dashboard['users_id'] === (int) Session::getLoginUserID()) {
            return true;
        }
        return (self::rightsForCurrentUser($dashboardId) & READ) === READ;
    }

    public static function canEdit(int $dashboardId): bool
    {
        if (Session::haveRight(Profile::RIGHT_MANAGE_DASHBOARDS, UPDATE)) {
            return true;
        }
        if (!Session::haveRight(Profile::RIGHT_VIEW_DASHBOARD, READ)) {
            return false;
        }
        $dashboard = Dashboard::find($dashboardId);
        if ($dashboard !== null && (int) $dashboard['users_id'] === (int) Session::getLoginUserID()) {
            return true;
        }
        return (self::rightsForCurrentUser($dashboardId) & UPDATE) === UPDATE;
    }

    public static function checkView(int $dashboardId): void
    {
        if (!self::canView($dashboardId)) {
            Html::displayErrorAndDie(__('Você não tem permissão para acessar este dashboard.', 'biforglpi'));
        }
    }

    public static function checkEdit(int $dashboardId): void
    {
        if (!self::canEdit($dashboardId)) {
            Html::displayErrorAndDie(__('Você não tem permissão para editar este dashboard.', 'biforglpi'));
        }
    }

    /** @return list<array<string, mixed>> */
    public static function allForDashboard(int $dashboardId): array
    {
        global $DB;
        $rows = [];
        foreach ($DB->request([
            'FROM' => self::TABLE,
            'WHERE' => ['dashboards_id' => $dashboardId],
            'ORDER' => ['itemtype ASC', 'items_id ASC'],
        ]) as $row) {
            $row['id'] = (int) $row['id'];
            $row['dashboards_id'] = (int) $row['dashboards_id'];
            $row['items_id'] = (int) $row['items_id'];
            $row['rights'] = (int) $row['rights'];
            $rows[] = $row;
        }
        return $rows;
    }

    /** @param array<string, mixed> $input */
    public static function save(int $dashboardId, array $input): void
    {
        global $DB;
        self::checkEdit($dashboardId);
        $itemtype = (string) ($input['itemtype'] ?? '');
        $itemsValue = $input['items_id'] ?? null;
        if (isset($input['target']) && is_string($input['target'])) {
            [$itemtype, $itemsValue] = array_pad(explode(':', $input['target'], 2), 2, null);
        }
        if (!in_array($itemtype, [self::ITEM_PROFILE, self::ITEM_GROUP], true)) {
            throw new InvalidArgumentException('Tipo de acesso inválido.');
        }
        $itemsId = filter_var($itemsValue, FILTER_VALIDATE_INT);
        if ($itemsId === false || $itemsId < 1) {
            throw new InvalidArgumentException('Selecione um perfil ou grupo.');
        }
        $rights = READ | (!empty($input['can_edit']) ? UPDATE : 0);
        $where = [
            'dashboards_id' => $dashboardId,
            'itemtype' => $itemtype,
            'items_id' => $itemsId,
        ];
        $exists = false;
        foreach ($DB->request(['FROM' => self::TABLE, 'WHERE' => $where, 'LIMIT' => 1]) as $row) {
            $exists = true;
            if (!$DB->update(self::TABLE, ['rights' => $rights], ['id' => (int) $row['id']])) {
                throw new RuntimeException('Não foi possível atualizar a permissão.');
            }
        }
        if (!$exists && !$DB->insert(self::TABLE, $where + ['rights' => $rights])) {
            throw new RuntimeException('Não foi possível salvar a permissão.');
        }
    }

    public static function delete(int $dashboardId, int $rightId): void
    {
        global $DB;
        self::checkEdit($dashboardId);
        if (!$DB->delete(self::TABLE, ['id' => $rightId, 'dashboards_id' => $dashboardId])) {
            throw new RuntimeException('Não foi possível remover a permissão.');
        }
    }

    public static function grantInstallerProfile(int $dashboardId): void
    {
        global $DB;
        $profileId = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
        if ($profileId > 0) {
            $DB->insert(self::TABLE, [
                'dashboards_id' => $dashboardId,
                'itemtype' => self::ITEM_PROFILE,
                'items_id' => $profileId,
                'rights' => READ | UPDATE,
            ]);
        }
    }

    private static function rightsForCurrentUser(int $dashboardId): int
    {
        global $DB;
        $profileId = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
        $groupIds = array_map('intval', (array) ($_SESSION['glpigroups'] ?? []));
        $rights = 0;
        foreach ($DB->request(['FROM' => self::TABLE, 'WHERE' => ['dashboards_id' => $dashboardId]]) as $row) {
            $matchesProfile = $row['itemtype'] === self::ITEM_PROFILE && (int) $row['items_id'] === $profileId;
            $matchesGroup = $row['itemtype'] === self::ITEM_GROUP && in_array((int) $row['items_id'], $groupIds, true);
            if ($matchesProfile || $matchesGroup) {
                $rights |= (int) $row['rights'];
            }
        }
        return $rights;
    }
}
