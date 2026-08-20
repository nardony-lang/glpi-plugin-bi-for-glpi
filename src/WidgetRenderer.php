<?php

namespace GlpiPlugin\Biforglpi;

use Throwable;

final class WidgetRenderer
{
    /** @param array<string, mixed> $widget @param array<string, mixed> $query @return array<string, mixed> */
    public function prepare(array $widget, array $query, bool $demo, array $filterContext): array
    {
        try {
            if ($demo) {
                $rows = $this->demoRows($widget);
                $columns = $rows === [] ? [] : array_map('strval', array_keys($rows[0]));
                $elapsedMs = null;
            } else {
                $sql = (new SqlTemplate())->compile((string) $query['query_sql'], $filterContext);
                $result = (new SqlExecutor())->execute(
                    $sql,
                    (int) $query['row_limit']
                );
                $rows = $result['rows'];
                $columns = $result['columns'];
                $elapsedMs = $result['elapsed_ms'];
            }

            $number = $this->numberData($rows, $widget);
            return [
                'ok' => true,
                'empty' => $rows === [],
                'columns' => $columns,
                'rows' => $rows,
                'elapsed_ms' => $elapsedMs,
                'chart' => $this->chartData($rows, $widget),
                'gauge' => $this->gaugeData($rows, $widget),
                'number' => $number,
                'value' => $number['value'],
            ];
        } catch (Throwable) {
            return [
                'ok' => false,
                'message' => __('Componente indisponível. Revise a consulta salva.', 'biforglpi'),
            ];
        }
    }

    /** @param list<array<string, mixed>> $rows @param array<string, mixed> $widget @return array<string, mixed> */
    private function chartData(array $rows, array $widget): array
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
        $type = (string) ($widget['widget_type'] ?? '');
        $settings = [];
        if ($type === 'bar') {
            $settings = array_merge(DashboardWidget::defaultBarSettings(), is_array($widget['settings'] ?? null) ? $widget['settings'] : []);
        } elseif ($type === 'line') {
            $settings = array_merge(DashboardWidget::defaultLineSettings(), is_array($widget['settings'] ?? null) ? $widget['settings'] : []);
        } elseif ($type === 'doughnut') {
            $settings = array_merge(DashboardWidget::defaultDoughnutSettings(), is_array($widget['settings'] ?? null) ? $widget['settings'] : []);
        }
        return ['labels' => $labels, 'values' => $values, 'settings' => $settings];
    }

    /** @param list<array<string, mixed>> $rows */
    private function numberData(array $rows, array $widget): array
    {
        if ($rows === [] || $rows[0] === [] || reset($rows[0]) === null) {
            return ['value' => __('Sem dados', 'biforglpi'), 'color' => null, 'target' => null];
        }
        $value = reset($rows[0]);
        $settings = array_merge(
            DashboardWidget::defaultNumberSettings(),
            is_array($widget['settings'] ?? null) ? $widget['settings'] : []
        );
        if (!is_numeric($value)) {
            return [
                'value' => (string) $settings['prefix'] . (string) $value . (string) $settings['suffix'],
                'color' => null,
                'target' => null,
            ];
        }
        $number = (float) $value;
        $decimals = (int) $settings['decimals'];
        if ($decimals < 0) {
            $decimals = floor($number) === $number ? 0 : 2;
        }
        $format = static fn(float $numeric): string => (string) $settings['prefix']
            . number_format($numeric, $decimals, ',', '.')
            . (string) $settings['suffix'];
        $color = null;
        if (!empty($settings['use_colors'])) {
            $candidate = $number < (float) $settings['warning']
                ? (string) $settings['color_low']
                : ($number < (float) $settings['success'] ? (string) $settings['color_mid'] : (string) $settings['color_high']);
            if (preg_match('/^#[0-9a-f]{6}$/i', $candidate) === 1) {
                $color = strtolower($candidate);
            }
        }
        return [
            'value' => $format($number),
            'color' => $color,
            'target' => $settings['target'] !== null ? $format((float) $settings['target']) : null,
        ];
    }

    /** @param list<array<string, mixed>> $rows @param array<string, mixed> $widget @return array<string, mixed>|null */
    private function gaugeData(array $rows, array $widget): ?array
    {
        if ($rows === [] || $rows[0] === []) {
            return null;
        }
        $value = reset($rows[0]);
        if (!is_numeric($value)) {
            return null;
        }
        $settings = array_merge(
            DashboardWidget::defaultGaugeSettings(),
            is_array($widget['settings'] ?? null) ? $widget['settings'] : []
        );
        return ['value' => (float) $value] + $settings;
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
        if ($widget['widget_type'] === 'gauge') {
            return [['valor' => 92]];
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
