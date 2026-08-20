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
                'table' => $this->tableData($columns, $rows, $widget),
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

    /**
     * @param list<string> $columns
     * @param list<array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function tableData(array $columns, array $rows, array $widget): array
    {
        $settings = array_merge(
            DashboardWidget::defaultTableSettings(),
            is_array($widget['settings'] ?? null) ? $widget['settings'] : []
        );
        $available = [];
        foreach ($columns as $column) {
            $available[strtolower($column)] = $column;
        }

        $definitions = [];
        $configured = [];
        foreach (is_array($settings['columns'] ?? null) ? $settings['columns'] : [] as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $requested = trim((string) ($rule['source'] ?? ''));
            $key = strtolower($requested);
            if ($requested === '' || !isset($available[$key]) || isset($configured[$key])) {
                continue;
            }
            $source = $available[$key];
            $definitions[] = $this->tableColumnDefinition($source, $rule);
            $configured[$key] = true;
        }
        if (!empty($settings['show_unconfigured']) || $definitions === []) {
            foreach ($columns as $column) {
                $key = strtolower($column);
                if (!isset($configured[$key])) {
                    $definitions[] = $this->tableColumnDefinition($column, []);
                }
            }
        }

        $formattedRows = [];
        foreach ($rows as $row) {
            $cells = [];
            foreach ($definitions as $definition) {
                $cells[] = $this->tableCell($row[$definition['source']] ?? null, $definition);
            }
            $formattedRows[] = $cells;
        }
        return ['settings' => $settings, 'columns' => $definitions, 'rows' => $formattedRows];
    }

    /** @param array<string, mixed> $rule @return array<string, mixed> */
    private function tableColumnDefinition(string $source, array $rule): array
    {
        $type = (string) ($rule['type'] ?? 'text');
        $numericTypes = ['number', 'percentage', 'progress'];
        $align = (string) ($rule['align'] ?? 'auto');
        if ($align === 'auto') {
            $align = in_array($type, $numericTypes, true) ? 'right' : (str_starts_with($type, 'sparkline_') ? 'center' : 'left');
        }
        return [
            'source' => $source,
            'label' => (string) (($rule['label'] ?? '') ?: $source),
            'type' => $type,
            'decimals' => (int) ($rule['decimals'] ?? -1),
            'prefix' => (string) ($rule['prefix'] ?? ''),
            'suffix' => (string) ($rule['suffix'] ?? ''),
            'width' => (int) ($rule['width'] ?? 0),
            'align' => $align,
            'color' => (string) ($rule['color'] ?? '#206bc4'),
            'min' => isset($rule['min']) && $rule['min'] !== null ? (float) $rule['min'] : null,
            'max' => isset($rule['max']) && $rule['max'] !== null ? (float) $rule['max'] : null,
        ];
    }

    /** @param array<string, mixed> $definition @return array<string, mixed> */
    private function tableCell(mixed $raw, array $definition): array
    {
        $type = (string) $definition['type'];
        $display = $raw === null
            ? ''
            : (is_scalar($raw) ? (string) $raw : (json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''));
        $cell = ['display' => $display, 'type' => $type];
        if ($type === 'duration') {
            if (is_numeric($raw)) {
                $seconds = max(0, (int) round((float) $raw));
                $cell['display'] = sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
            }
            return $cell;
        }
        if ($type === 'badge') {
            $normalized = strtolower(trim($display));
            $cell['badge_color'] = in_array($normalized, ['ativo', 'active', 'ok', 'sucesso', 'sim', 'solucionado'], true)
                ? 'green'
                : (in_array($normalized, ['atenção', 'atencao', 'pendente', 'planejado', 'em atendimento'], true)
                    ? 'yellow'
                    : (in_array($normalized, ['crítico', 'critico', 'erro', 'não', 'nao', 'atrasado'], true) ? 'red' : 'azure'));
            return $cell;
        }
        if ($type === 'sparkline_line' || $type === 'sparkline_bar') {
            $series = $this->tableSeries($raw);
            $cell['series'] = $series;
            $cell['display'] = $series === [] ? $display : $this->formatTableNumber((float) end($series), $definition);
            $cell['color'] = $definition['color'];
            return $cell;
        }
        if (!in_array($type, ['number', 'percentage', 'progress'], true) || !is_numeric($raw)) {
            return $cell;
        }

        $numeric = (float) $raw;
        $format = $definition;
        if ($type === 'percentage' && $format['suffix'] === '') {
            $format['suffix'] = '%';
        }
        $cell['display'] = $this->formatTableNumber($numeric, $format);
        if ($type === 'progress') {
            $min = $definition['min'] ?? 0.0;
            $max = $definition['max'] ?? 100.0;
            $range = $max - $min;
            $cell['percent'] = $range > 0 ? max(0.0, min(100.0, (($numeric - $min) / $range) * 100.0)) : 0.0;
            $cell['color'] = $definition['color'];
        }
        return $cell;
    }

    /** @return list<float> */
    private function tableSeries(mixed $raw): array
    {
        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($decoded) || !array_is_list($decoded)) {
            return [];
        }
        $series = [];
        foreach (array_slice($decoded, 0, 60) as $value) {
            if (is_numeric($value)) {
                $series[] = (float) $value;
            }
        }
        return $series;
    }

    /** @param array<string, mixed> $definition */
    private function formatTableNumber(float $value, array $definition): string
    {
        $decimals = (int) ($definition['decimals'] ?? -1);
        if ($decimals < 0) {
            $decimals = floor($value) === $value ? 0 : 2;
        }
        return (string) ($definition['prefix'] ?? '')
            . number_format($value, $decimals, ',', '.')
            . (string) ($definition['suffix'] ?? '');
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
