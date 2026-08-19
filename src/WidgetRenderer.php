<?php

namespace GlpiPlugin\Biforglpi;

use Throwable;

final class WidgetRenderer
{
    /** @param array<string, mixed> $widget @param array<string, mixed> $query @return array<string, mixed> */
    public function prepare(array $widget, array $query, bool $demo): array
    {
        try {
            if ($demo) {
                $rows = $this->demoRows($widget);
                $columns = $rows === [] ? [] : array_map('strval', array_keys($rows[0]));
                $elapsedMs = null;
            } else {
                $result = (new SqlExecutor())->execute(
                    (string) $query['query_sql'],
                    (int) $query['row_limit']
                );
                $rows = $result['rows'];
                $columns = $result['columns'];
                $elapsedMs = $result['elapsed_ms'];
            }

            return [
                'ok' => true,
                'empty' => $rows === [],
                'columns' => $columns,
                'rows' => $rows,
                'elapsed_ms' => $elapsedMs,
                'chart' => $this->chartData($rows),
                'value' => $this->numberValue($rows),
            ];
        } catch (Throwable) {
            return [
                'ok' => false,
                'message' => __('Componente indisponível. Revise a consulta salva.', 'biforglpi'),
            ];
        }
    }

    /** @param list<array<string, mixed>> $rows @return array{labels:list<string>,values:list<float>} */
    private function chartData(array $rows): array
    {
        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $valuesInRow = array_values($row);
            if (count($valuesInRow) < 2 || !is_numeric($valuesInRow[1])) {
                continue;
            }
            $labels[] = (string) $valuesInRow[0];
            $values[] = (float) $valuesInRow[1];
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /** @param list<array<string, mixed>> $rows */
    private function numberValue(array $rows): string
    {
        if ($rows === [] || $rows[0] === []) {
            return __('Sem dados', 'biforglpi');
        }
        $value = reset($rows[0]);
        if (!is_numeric($value)) {
            return (string) $value;
        }
        $number = (float) $value;
        return floor($number) === $number
            ? number_format($number, 0, ',', '.')
            : number_format($number, 2, ',', '.');
    }

    /** @param array<string, mixed> $widget @return list<array<string, mixed>> */
    private function demoRows(array $widget): array
    {
        if (!empty($widget['demo_data'])) {
            $decoded = json_decode((string) $widget['demo_data'], true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, 'is_array'));
            }
        }
        if ($widget['widget_type'] === 'number') {
            return [['valor' => 128]];
        }
        return [
            ['categoria' => 'Jan', 'valor' => 18],
            ['categoria' => 'Fev', 'valor' => 24],
            ['categoria' => 'Mar', 'valor' => 21],
            ['categoria' => 'Abr', 'valor' => 31],
        ];
    }
}
