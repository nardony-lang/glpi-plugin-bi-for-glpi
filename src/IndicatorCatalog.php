<?php

namespace GlpiPlugin\Biforglpi;

final class IndicatorCatalog
{
    /** @return array<string,array<string,mixed>> */
    public static function all(): array
    {
        return [
            'solved_total' => [
                'name' => 'Requisições solucionadas',
                'description' => 'Quantidade de requisições solucionadas no período.',
                'query_sql' => "SELECT COUNT(*) AS total\nFROM glpi_tickets\nWHERE entities_id = {{entity_id}}\n  AND type = 2\n  AND status IN (5, 6)\n  AND is_deleted = 0\n  AND solvedate >= {{date_start}}\n  AND solvedate < {{date_end_exclusive}}",
                'visualization' => SavedQuery::TYPE_NUMBER, 'widget_type' => 'number', 'row_limit' => 1, 'width' => 4,
            ],
            'average_resolution' => [
                'name' => 'Tempo médio de resolução',
                'description' => 'Tempo médio de resolução das requisições no formato HH:MM:SS.',
                'query_sql' => "SELECT COALESCE(TIME_FORMAT(SEC_TO_TIME(ROUND(AVG(solve_delay_stat))), '%H:%i:%s'), '00:00:00') AS media_resolucao\nFROM glpi_tickets\nWHERE entities_id = {{entity_id}}\n  AND type = 2\n  AND status IN (5, 6)\n  AND is_deleted = 0\n  AND solvedate >= {{date_start}}\n  AND solvedate < {{date_end_exclusive}}",
                'visualization' => SavedQuery::TYPE_NUMBER, 'widget_type' => 'number', 'row_limit' => 1, 'width' => 4,
            ],
            'solved_by_group' => [
                'name' => 'Requisições solucionadas por grupo',
                'description' => 'Quantidade solucionada por grupo solucionador no período.',
                'query_sql' => "SELECT g.completename AS grupo, COUNT(DISTINCT t.id) AS total\nFROM glpi_tickets t\nINNER JOIN glpi_groups_tickets gt ON gt.tickets_id = t.id AND gt.type = 2\nINNER JOIN glpi_groups g ON g.id = gt.groups_id\nWHERE t.entities_id = {{entity_id}}\n  AND t.type = 2\n  AND t.status IN (5, 6)\n  AND t.is_deleted = 0\n  AND t.solvedate >= {{date_start}}\n  AND t.solvedate < {{date_end_exclusive}}\nGROUP BY g.id, g.completename\nORDER BY total DESC",
                'visualization' => SavedQuery::TYPE_TABLE, 'widget_type' => 'bar', 'row_limit' => 100, 'width' => 12,
            ],
            'within_sla' => [
                'name' => 'Percentual solucionado dentro do ANS',
                'description' => 'Percentual das requisições com ANS solucionadas dentro do prazo.',
                'query_sql' => "SELECT CASE WHEN COUNT(*) = 0 THEN NULL ELSE ROUND(100 * SUM(CASE WHEN solvedate <= time_to_resolve THEN 1 ELSE 0 END) / COUNT(*), 2) END AS percentual\nFROM glpi_tickets\nWHERE entities_id = {{entity_id}}\n  AND type = 2\n  AND status IN (5, 6)\n  AND is_deleted = 0\n  AND time_to_resolve IS NOT NULL\n  AND solvedate >= {{date_start}}\n  AND solvedate < {{date_end_exclusive}}",
                'visualization' => SavedQuery::TYPE_NUMBER, 'widget_type' => 'gauge', 'row_limit' => 1, 'width' => 4,
                'widget_settings' => DashboardWidget::defaultGaugeSettings(),
            ],
            'average_by_group' => [
                'name' => 'Tempo médio por grupo solucionador',
                'description' => 'Tempo médio de resolução em horas por grupo solucionador.',
                'query_sql' => "SELECT g.completename AS grupo, ROUND(AVG(t.solve_delay_stat) / 3600, 2) AS media_horas\nFROM glpi_tickets t\nINNER JOIN glpi_groups_tickets gt ON gt.tickets_id = t.id AND gt.type = 2\nINNER JOIN glpi_groups g ON g.id = gt.groups_id\nWHERE t.entities_id = {{entity_id}}\n  AND t.type = 2\n  AND t.status IN (5, 6)\n  AND t.is_deleted = 0\n  AND t.solvedate >= {{date_start}}\n  AND t.solvedate < {{date_end_exclusive}}\nGROUP BY g.id, g.completename\nORDER BY media_horas DESC",
                'visualization' => SavedQuery::TYPE_TABLE, 'widget_type' => 'bar', 'row_limit' => 100, 'width' => 12,
            ],
        ];
    }

    /** @return array<string,mixed>|null */
    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
