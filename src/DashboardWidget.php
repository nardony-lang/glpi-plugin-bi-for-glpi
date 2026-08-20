<?php

namespace GlpiPlugin\Biforglpi;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class DashboardWidget
{
    public const TABLE = 'glpi_plugin_biforglpi_dashboardwidgets';
    public const TYPES = ['number', 'table', 'bar', 'line', 'doughnut', 'gauge'];

    /** @return array<string, float|string|null> */
    public static function defaultGaugeSettings(): array
    {
        return [
            'min' => 0.0,
            'max' => 100.0,
            'target' => 95.0,
            'warning' => 80.0,
            'success' => 95.0,
            'unit' => '%',
            'color_low' => '#d63939',
            'color_mid' => '#f59f00',
            'color_high' => '#2fb344',
        ];
    }

    /** @return array<string, float|int|string|null> */
    public static function defaultNumberSettings(): array
    {
        return [
            'decimals' => -1,
            'prefix' => '',
            'suffix' => '',
            'target' => null,
            'use_colors' => 0,
            'warning' => 80.0,
            'success' => 95.0,
            'color_low' => '#d63939',
            'color_mid' => '#f59f00',
            'color_high' => '#2fb344',
        ];
    }

    /** @return array<string, int|string> */
    public static function defaultBarSettings(): array
    {
        return [
            'orientation' => 'vertical',
            'color' => '#206bc4',
            'use_palette' => 0,
            'show_values' => 1,
            'show_grid' => 1,
            'decimals' => -1,
            'unit' => '',
        ];
    }

    /** @return array<string, int|string> */
    public static function defaultLineSettings(): array
    {
        return [
            'color' => '#206bc4',
            'show_values' => 0,
            'show_grid' => 1,
            'show_points' => 1,
            'fill_area' => 1,
            'smooth' => 1,
            'decimals' => -1,
            'unit' => '',
        ];
    }

    /** @return array<string, int|string> */
    public static function defaultDoughnutSettings(): array
    {
        return [
            'legend_position' => 'right',
            'hole_size' => 52,
            'show_labels' => 1,
            'show_percentages' => 1,
            'decimals' => 0,
            'unit' => '',
        ];
    }

    /** @return array<string, mixed> */
    public static function defaultTableSettings(): array
    {
        return [
            'striped' => 1,
            'compact' => 1,
            'sticky_header' => 0,
            'show_unconfigured' => 1,
            'export_png' => 1,
            'export_pdf' => 1,
            'columns' => [],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function allForDashboard(int $dashboardId): array
    {
        global $DB;
        $rows = [];
        foreach ($DB->request([
            'FROM' => self::TABLE,
            'WHERE' => ['dashboards_id' => $dashboardId],
            'ORDER' => ['position ASC', 'id ASC'],
        ]) as $row) {
            $rows[] = self::castRow($row);
        }
        return $rows;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        global $DB;
        foreach ($DB->request(['FROM' => self::TABLE, 'WHERE' => ['id' => $id], 'LIMIT' => 1]) as $row) {
            return self::castRow($row);
        }
        return null;
    }

    /** @param array<string, mixed> $input */
    public static function create(int $dashboardId, array $input): int
    {
        global $DB;
        DashboardAccess::checkEdit($dashboardId);
        $data = self::validate($input) + ['dashboards_id' => $dashboardId];
        $now = date('Y-m-d H:i:s');
        $data['date_creation'] = $now;
        $data['date_mod'] = $now;
        if (!$DB->insert(self::TABLE, $data)) {
            throw new RuntimeException('Não foi possível salvar o componente.');
        }
        return (int) $DB->insertId();
    }

    /** @param array<string, mixed> $input */
    public static function update(int $id, array $input): void
    {
        global $DB;
        $widget = self::find($id);
        if ($widget === null) {
            throw new InvalidArgumentException('Componente não encontrado.');
        }
        DashboardAccess::checkEdit((int) $widget['dashboards_id']);
        $data = self::validate($input);
        $data['date_mod'] = date('Y-m-d H:i:s');
        if (!$DB->update(self::TABLE, $data, ['id' => $id])) {
            throw new RuntimeException('Não foi possível atualizar o componente.');
        }
    }

    public static function delete(int $id): void
    {
        global $DB;
        $widget = self::find($id);
        if ($widget === null) {
            return;
        }
        DashboardAccess::checkEdit((int) $widget['dashboards_id']);
        if (!$DB->delete(self::TABLE, ['id' => $id])) {
            throw new RuntimeException('Não foi possível excluir o componente.');
        }
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public static function validate(array $input): array
    {
        $savedQueryId = filter_var($input['savedqueries_id'] ?? null, FILTER_VALIDATE_INT);
        if ($savedQueryId === false || $savedQueryId < 1) {
            throw new InvalidArgumentException('Selecione uma consulta salva.');
        }
        $title = trim((string) ($input['title'] ?? ''));
        if (strlen($title) > 255) {
            throw new InvalidArgumentException('O título deve ter até 255 caracteres.');
        }
        $type = (string) ($input['widget_type'] ?? 'number');
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Tipo de componente inválido.');
        }
        $width = filter_var($input['width'] ?? 4, FILTER_VALIDATE_INT);
        if ($width === false || !in_array($width, [3, 4, 6, 8, 12], true)) {
            throw new InvalidArgumentException('Largura de componente inválida.');
        }
        $position = filter_var($input['position'] ?? 0, FILTER_VALIDATE_INT);
        if ($position === false || $position < 0 || $position > 999) {
            throw new InvalidArgumentException('A posição deve estar entre 0 e 999.');
        }
        $demoData = trim((string) ($input['demo_data'] ?? ''));
        if ($demoData !== '') {
            try {
                $decoded = json_decode($demoData, true, 32, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('Os dados de demonstração devem estar em JSON válido.', 0, $exception);
            }
            if (!is_array($decoded) || !array_is_list($decoded)) {
                throw new InvalidArgumentException('Os dados de demonstração devem ser uma lista JSON.');
            }
            $demoData = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
        return [
            'savedqueries_id' => (int) $savedQueryId,
            'title' => $title !== '' ? $title : null,
            'widget_type' => $type,
            'position' => (int) $position,
            'width' => (int) $width,
            'demo_data' => $demoData !== '' ? $demoData : null,
            'settings_json' => self::validateSettings($input, $type),
        ];
    }

    /** @param list<mixed> $widgetIds @param array<int|string,mixed> $widths */
    public static function updateLayout(int $dashboardId, array $widgetIds, array $widths): void
    {
        global $DB;
        DashboardAccess::checkEdit($dashboardId);
        $widgets = self::allForDashboard($dashboardId);
        $allowedIds = array_map(static fn(array $widget): int => (int) $widget['id'], $widgets);
        $layout = self::normalizeLayout($widgetIds, $widths, $allowedIds);
        $now = date('Y-m-d H:i:s');
        foreach ($layout as $item) {
            if (!$DB->update(self::TABLE, [
                'position' => $item['position'],
                'width' => $item['width'],
                'date_mod' => $now,
            ], ['id' => $item['id'], 'dashboards_id' => $dashboardId])) {
                throw new RuntimeException('Não foi possível salvar o layout do dashboard.');
            }
        }
    }

    public static function duplicate(int $id): int
    {
        global $DB;
        $widget = self::find($id);
        if ($widget === null) {
            throw new InvalidArgumentException('Componente não encontrado.');
        }
        $dashboardId = (int) $widget['dashboards_id'];
        DashboardAccess::checkEdit($dashboardId);
        $position = 0;
        foreach (self::allForDashboard($dashboardId) as $existing) {
            $position = max($position, (int) $existing['position'] + 1);
        }
        $title = trim((string) ($widget['title'] ?? ''));
        $now = date('Y-m-d H:i:s');
        if (!$DB->insert(self::TABLE, [
            'dashboards_id' => $dashboardId,
            'savedqueries_id' => (int) $widget['savedqueries_id'],
            'title' => $title !== '' ? substr($title, 0, 240) . ' (cópia)' : null,
            'widget_type' => (string) $widget['widget_type'],
            'position' => $position,
            'width' => (int) $widget['width'],
            'demo_data' => ($widget['demo_data'] ?? null) ?: null,
            'settings_json' => ($widget['settings_json'] ?? null) ?: null,
            'date_creation' => $now,
            'date_mod' => $now,
        ])) {
            throw new RuntimeException('Não foi possível duplicar o componente.');
        }
        return (int) $DB->insertId();
    }

    /**
     * @param list<mixed> $widgetIds
     * @param array<int|string,mixed> $widths
     * @param list<int> $allowedIds
     * @return list<array{id:int,width:int,position:int}>
     */
    public static function normalizeLayout(array $widgetIds, array $widths, array $allowedIds): array
    {
        $layout = [];
        $seen = [];
        foreach ($widgetIds as $index => $rawId) {
            $id = filter_var($rawId, FILTER_VALIDATE_INT);
            if ($id === false || !in_array((int) $id, $allowedIds, true) || isset($seen[(int) $id])) {
                throw new InvalidArgumentException('O layout contém um componente inválido.');
            }
            $width = filter_var($widths[(int) $id] ?? null, FILTER_VALIDATE_INT);
            if ($width === false || !in_array((int) $width, [3, 4, 6, 8, 12], true)) {
                throw new InvalidArgumentException('O layout contém uma largura inválida.');
            }
            $seen[(int) $id] = true;
            $layout[] = ['id' => (int) $id, 'width' => (int) $width, 'position' => $index];
        }
        if (count($seen) !== count($allowedIds)) {
            throw new InvalidArgumentException('Atualize a página antes de salvar o layout.');
        }
        return $layout;
    }

    /** @param array<string, mixed> $input */
    private static function validateSettings(array $input, string $type): ?string
    {
        if ($type === 'table') {
            return self::validateTableSettings($input);
        }
        if ($type === 'number') {
            return self::validateNumberSettings($input);
        }
        if ($type === 'bar' || $type === 'line') {
            return self::validateChartSettings($input, $type);
        }
        if ($type === 'doughnut') {
            return self::validateDoughnutSettings($input);
        }
        if ($type !== 'gauge') {
            return null;
        }
        return self::validateGaugeSettings($input);
    }

    /** @param array<string, mixed> $input */
    private static function validateTableSettings(array $input): string
    {
        $provided = [];
        $encoded = trim((string) ($input['settings_json'] ?? ''));
        if ($encoded !== '') {
            try {
                $decoded = json_decode($encoded, true, 32, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('A configuração da tabela é inválida.', 0, $exception);
            }
            if (!is_array($decoded)) {
                throw new InvalidArgumentException('A configuração da tabela é inválida.');
            }
            $provided = $decoded;
        }

        $defaults = self::defaultTableSettings();
        $settings = [];
        foreach (['striped', 'compact', 'sticky_header', 'show_unconfigured', 'export_png', 'export_pdf'] as $field) {
            $settings[$field] = !empty($input['table_' . $field] ?? $provided[$field] ?? $defaults[$field]) ? 1 : 0;
        }

        $sources = is_array($input['table_column_source'] ?? null) ? $input['table_column_source'] : [];
        if ($sources === [] && is_array($provided['columns'] ?? null)) {
            $settings['columns'] = self::normalizeTableColumns($provided['columns']);
            return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
        if (count($sources) > 30) {
            throw new InvalidArgumentException('A tabela permite configurar até 30 colunas.');
        }

        $columns = [];
        $seen = [];
        foreach ($sources as $index => $rawSource) {
            $source = trim((string) $rawSource);
            if ($source === '') {
                continue;
            }
            $column = [
                'source' => $source,
                'label' => trim((string) (($input['table_column_label'][$index] ?? '') ?: $source)),
                'type' => (string) ($input['table_column_type'][$index] ?? 'text'),
                'decimals' => $input['table_column_decimals'][$index] ?? -1,
                'prefix' => (string) ($input['table_column_prefix'][$index] ?? ''),
                'suffix' => (string) ($input['table_column_suffix'][$index] ?? ''),
                'width' => $input['table_column_width'][$index] ?? 0,
                'align' => (string) ($input['table_column_align'][$index] ?? 'auto'),
                'color' => (string) ($input['table_column_color'][$index] ?? '#206bc4'),
                'min' => $input['table_column_min'][$index] ?? null,
                'max' => $input['table_column_max'][$index] ?? null,
            ];
            $normalized = self::normalizeTableColumn($column);
            $key = strtolower($normalized['source']);
            if (isset($seen[$key])) {
                throw new InvalidArgumentException('Cada coluna de origem pode ser configurada apenas uma vez.');
            }
            $seen[$key] = true;
            $columns[] = $normalized;
        }
        $settings['columns'] = $columns;
        return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @param list<mixed> $columns @return list<array<string, mixed>> */
    private static function normalizeTableColumns(array $columns): array
    {
        if (count($columns) > 30) {
            throw new InvalidArgumentException('A tabela permite configurar até 30 colunas.');
        }
        $normalized = [];
        $seen = [];
        foreach ($columns as $column) {
            if (!is_array($column) || trim((string) ($column['source'] ?? '')) === '') {
                continue;
            }
            $item = self::normalizeTableColumn($column);
            $key = strtolower($item['source']);
            if (isset($seen[$key])) {
                throw new InvalidArgumentException('Cada coluna de origem pode ser configurada apenas uma vez.');
            }
            $seen[$key] = true;
            $normalized[] = $item;
        }
        return $normalized;
    }

    /** @param array<string, mixed> $column @return array<string, mixed> */
    private static function normalizeTableColumn(array $column): array
    {
        $source = trim((string) ($column['source'] ?? ''));
        $label = trim((string) ($column['label'] ?? $source));
        if (strlen($source) > 128 || strlen($label) > 128) {
            throw new InvalidArgumentException('Os nomes das colunas devem ter até 128 caracteres.');
        }
        $type = (string) ($column['type'] ?? 'text');
        $types = ['text', 'number', 'percentage', 'duration', 'badge', 'progress', 'sparkline_line', 'sparkline_bar'];
        if (!in_array($type, $types, true)) {
            throw new InvalidArgumentException('O tipo de apresentação da coluna é inválido.');
        }
        $decimals = filter_var($column['decimals'] ?? -1, FILTER_VALIDATE_INT);
        if ($decimals === false || $decimals < -1 || $decimals > 6) {
            throw new InvalidArgumentException('As casas decimais da coluna devem estar entre 0 e 6, ou no modo automático.');
        }
        $prefix = (string) ($column['prefix'] ?? '');
        $suffix = (string) ($column['suffix'] ?? '');
        if (strlen($prefix) > 20 || strlen($suffix) > 20) {
            throw new InvalidArgumentException('Prefixo e sufixo da coluna devem ter até 20 caracteres.');
        }
        $width = filter_var($column['width'] ?? 0, FILTER_VALIDATE_INT);
        if ($width === false || ($width !== 0 && ($width < 60 || $width > 600))) {
            throw new InvalidArgumentException('A largura da coluna deve ser automática ou ficar entre 60 e 600 pixels.');
        }
        $align = (string) ($column['align'] ?? 'auto');
        if (!in_array($align, ['auto', 'left', 'center', 'right'], true)) {
            throw new InvalidArgumentException('O alinhamento da coluna é inválido.');
        }
        $color = strtolower((string) ($column['color'] ?? '#206bc4'));
        if (preg_match('/^#[0-9a-f]{6}$/', $color) !== 1) {
            throw new InvalidArgumentException('A cor da coluna deve estar no formato hexadecimal.');
        }
        $limits = [];
        foreach (['min', 'max'] as $field) {
            $value = $column[$field] ?? null;
            if ($value === '' || $value === null) {
                $limits[$field] = null;
                continue;
            }
            $numeric = filter_var($value, FILTER_VALIDATE_FLOAT);
            if ($numeric === false) {
                throw new InvalidArgumentException('Os limites da coluna devem ser numéricos.');
            }
            $limits[$field] = (float) $numeric;
        }
        if ($limits['min'] !== null && $limits['max'] !== null && $limits['min'] >= $limits['max']) {
            throw new InvalidArgumentException('O limite mínimo da coluna deve ser menor que o máximo.');
        }
        return [
            'source' => $source,
            'label' => $label !== '' ? $label : $source,
            'type' => $type,
            'decimals' => (int) $decimals,
            'prefix' => $prefix,
            'suffix' => $suffix,
            'width' => (int) $width,
            'align' => $align,
            'color' => $color,
            'min' => $limits['min'],
            'max' => $limits['max'],
        ];
    }

    /** @param array<string, mixed> $input */
    private static function validateGaugeSettings(array $input): string
    {
        $provided = [];
        $encoded = trim((string) ($input['settings_json'] ?? ''));
        if ($encoded !== '') {
            try {
                $decoded = json_decode($encoded, true, 16, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('A configuração do Gauge é inválida.', 0, $exception);
            }
            if (!is_array($decoded)) {
                throw new InvalidArgumentException('A configuração do Gauge é inválida.');
            }
            $provided = $decoded;
        }

        $defaults = self::defaultGaugeSettings();
        $settings = [];
        foreach (['min', 'max', 'warning', 'success'] as $field) {
            $value = filter_var($input['gauge_' . $field] ?? $provided[$field] ?? $defaults[$field], FILTER_VALIDATE_FLOAT);
            if ($value === false) {
                throw new InvalidArgumentException('Os valores do Gauge devem ser numéricos.');
            }
            $settings[$field] = (float) $value;
        }

        $targetValue = $input['gauge_target'] ?? $provided['target'] ?? $defaults['target'];
        if ($targetValue === '' || $targetValue === null) {
            $settings['target'] = null;
        } else {
            $target = filter_var($targetValue, FILTER_VALIDATE_FLOAT);
            if ($target === false) {
                throw new InvalidArgumentException('A meta do Gauge deve ser numérica.');
            }
            $settings['target'] = (float) $target;
        }

        if ($settings['min'] >= $settings['max']) {
            throw new InvalidArgumentException('O valor mínimo do Gauge deve ser menor que o máximo.');
        }
        if ($settings['warning'] < $settings['min'] || $settings['warning'] > $settings['success'] || $settings['success'] > $settings['max']) {
            throw new InvalidArgumentException('As faixas do Gauge devem estar em ordem entre o mínimo e o máximo.');
        }
        if ($settings['target'] !== null && ($settings['target'] < $settings['min'] || $settings['target'] > $settings['max'])) {
            throw new InvalidArgumentException('A meta do Gauge deve ficar entre o mínimo e o máximo.');
        }

        $unit = trim((string) ($input['gauge_unit'] ?? $provided['unit'] ?? $defaults['unit']));
        if (strlen($unit) > 20) {
            throw new InvalidArgumentException('A unidade do Gauge deve ter até 20 caracteres.');
        }
        $settings['unit'] = $unit;

        foreach (['color_low', 'color_mid', 'color_high'] as $field) {
            $color = strtolower((string) ($input['gauge_' . $field] ?? $provided[$field] ?? $defaults[$field]));
            if (preg_match('/^#[0-9a-f]{6}$/', $color) !== 1) {
                throw new InvalidArgumentException('As cores do Gauge devem estar no formato hexadecimal.');
            }
            $settings[$field] = $color;
        }

        return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $input */
    private static function validateDoughnutSettings(array $input): string
    {
        $provided = [];
        $encoded = trim((string) ($input['settings_json'] ?? ''));
        if ($encoded !== '') {
            try {
                $decoded = json_decode($encoded, true, 16, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('A configuração do gráfico de rosca é inválida.', 0, $exception);
            }
            if (!is_array($decoded)) {
                throw new InvalidArgumentException('A configuração do gráfico de rosca é inválida.');
            }
            $provided = $decoded;
        }

        $defaults = self::defaultDoughnutSettings();
        $legendPosition = (string) ($input['doughnut_legend_position'] ?? $provided['legend_position'] ?? $defaults['legend_position']);
        if (!in_array($legendPosition, ['right', 'bottom', 'hidden'], true)) {
            throw new InvalidArgumentException('A posição da legenda do gráfico de rosca é inválida.');
        }
        $holeSize = filter_var($input['doughnut_hole_size'] ?? $provided['hole_size'] ?? $defaults['hole_size'], FILTER_VALIDATE_INT);
        if ($holeSize === false || $holeSize < 20 || $holeSize > 70) {
            throw new InvalidArgumentException('O tamanho do centro da rosca deve estar entre 20 e 70%.');
        }
        $decimals = filter_var($input['doughnut_decimals'] ?? $provided['decimals'] ?? $defaults['decimals'], FILTER_VALIDATE_INT);
        if ($decimals === false || $decimals < -1 || $decimals > 6) {
            throw new InvalidArgumentException('As casas decimais do gráfico de rosca devem estar entre 0 e 6, ou no modo automático.');
        }
        $unit = trim((string) ($input['doughnut_unit'] ?? $provided['unit'] ?? $defaults['unit']));
        if (strlen($unit) > 20) {
            throw new InvalidArgumentException('A unidade do gráfico de rosca deve ter até 20 caracteres.');
        }

        $settings = [
            'legend_position' => $legendPosition,
            'hole_size' => (int) $holeSize,
            'show_labels' => !empty($input['doughnut_show_labels'] ?? $provided['show_labels'] ?? $defaults['show_labels']) ? 1 : 0,
            'show_percentages' => !empty($input['doughnut_show_percentages'] ?? $provided['show_percentages'] ?? $defaults['show_percentages']) ? 1 : 0,
            'decimals' => (int) $decimals,
            'unit' => $unit,
        ];
        return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $input */
    private static function validateChartSettings(array $input, string $type): string
    {
        $provided = [];
        $encoded = trim((string) ($input['settings_json'] ?? ''));
        if ($encoded !== '') {
            try {
                $decoded = json_decode($encoded, true, 16, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('A configuração do gráfico é inválida.', 0, $exception);
            }
            if (!is_array($decoded)) {
                throw new InvalidArgumentException('A configuração do gráfico é inválida.');
            }
            $provided = $decoded;
        }

        $prefix = $type . '_';
        $defaults = $type === 'bar' ? self::defaultBarSettings() : self::defaultLineSettings();
        $decimals = filter_var($input[$prefix . 'decimals'] ?? $provided['decimals'] ?? $defaults['decimals'], FILTER_VALIDATE_INT);
        if ($decimals === false || $decimals < -1 || $decimals > 6) {
            throw new InvalidArgumentException('As casas decimais do gráfico devem estar entre 0 e 6, ou no modo automático.');
        }
        $unit = trim((string) ($input[$prefix . 'unit'] ?? $provided['unit'] ?? $defaults['unit']));
        if (strlen($unit) > 20) {
            throw new InvalidArgumentException('A unidade do gráfico deve ter até 20 caracteres.');
        }
        $color = strtolower((string) ($input[$prefix . 'color'] ?? $provided['color'] ?? $defaults['color']));
        if (preg_match('/^#[0-9a-f]{6}$/', $color) !== 1) {
            throw new InvalidArgumentException('A cor do gráfico deve estar no formato hexadecimal.');
        }

        $settings = [
            'color' => $color,
            'show_values' => !empty($input[$prefix . 'show_values'] ?? $provided['show_values'] ?? $defaults['show_values']) ? 1 : 0,
            'show_grid' => !empty($input[$prefix . 'show_grid'] ?? $provided['show_grid'] ?? $defaults['show_grid']) ? 1 : 0,
            'decimals' => (int) $decimals,
            'unit' => $unit,
        ];
        if ($type === 'bar') {
            $orientation = (string) ($input['bar_orientation'] ?? $provided['orientation'] ?? $defaults['orientation']);
            if (!in_array($orientation, ['vertical', 'horizontal'], true)) {
                throw new InvalidArgumentException('A orientação do gráfico de barras é inválida.');
            }
            $settings['orientation'] = $orientation;
            $settings['use_palette'] = !empty($input['bar_use_palette'] ?? $provided['use_palette'] ?? $defaults['use_palette']) ? 1 : 0;
        } else {
            foreach (['show_points', 'fill_area', 'smooth'] as $field) {
                $settings[$field] = !empty($input['line_' . $field] ?? $provided[$field] ?? $defaults[$field]) ? 1 : 0;
            }
        }
        return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $input */
    private static function validateNumberSettings(array $input): string
    {
        $provided = [];
        $encoded = trim((string) ($input['settings_json'] ?? ''));
        if ($encoded !== '') {
            try {
                $decoded = json_decode($encoded, true, 16, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('A configuração do indicador numérico é inválida.', 0, $exception);
            }
            if (!is_array($decoded)) {
                throw new InvalidArgumentException('A configuração do indicador numérico é inválida.');
            }
            $provided = $decoded;
        }

        $defaults = self::defaultNumberSettings();
        $decimals = filter_var($input['number_decimals'] ?? $provided['decimals'] ?? $defaults['decimals'], FILTER_VALIDATE_INT);
        if ($decimals === false || $decimals < -1 || $decimals > 6) {
            throw new InvalidArgumentException('As casas decimais devem estar entre 0 e 6, ou no modo automático.');
        }
        $prefix = (string) ($input['number_prefix'] ?? $provided['prefix'] ?? $defaults['prefix']);
        $suffix = (string) ($input['number_suffix'] ?? $provided['suffix'] ?? $defaults['suffix']);
        if (strlen($prefix) > 20 || strlen($suffix) > 20) {
            throw new InvalidArgumentException('Prefixo e sufixo devem ter até 20 caracteres.');
        }

        $targetValue = $input['number_target'] ?? $provided['target'] ?? $defaults['target'];
        $target = null;
        if ($targetValue !== '' && $targetValue !== null) {
            $target = filter_var($targetValue, FILTER_VALIDATE_FLOAT);
            if ($target === false) {
                throw new InvalidArgumentException('A meta do indicador deve ser numérica.');
            }
            $target = (float) $target;
        }

        $warning = filter_var($input['number_warning'] ?? $provided['warning'] ?? $defaults['warning'], FILTER_VALIDATE_FLOAT);
        $success = filter_var($input['number_success'] ?? $provided['success'] ?? $defaults['success'], FILTER_VALIDATE_FLOAT);
        if ($warning === false || $success === false || $warning > $success) {
            throw new InvalidArgumentException('As faixas do indicador devem estar em ordem crescente.');
        }

        $settings = [
            'decimals' => (int) $decimals,
            'prefix' => $prefix,
            'suffix' => $suffix,
            'target' => $target,
            'use_colors' => !empty($input['number_use_colors'] ?? $provided['use_colors'] ?? 0) ? 1 : 0,
            'warning' => (float) $warning,
            'success' => (float) $success,
        ];
        foreach (['color_low', 'color_mid', 'color_high'] as $field) {
            $color = strtolower((string) ($input['number_' . $field] ?? $provided[$field] ?? $defaults[$field]));
            if (preg_match('/^#[0-9a-f]{6}$/', $color) !== 1) {
                throw new InvalidArgumentException('As cores do indicador devem estar no formato hexadecimal.');
            }
            $settings[$field] = $color;
        }
        return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private static function castRow(array $row): array
    {
        foreach (['id', 'dashboards_id', 'savedqueries_id', 'position', 'width'] as $field) {
            $row[$field] = (int) $row[$field];
        }
        $settings = json_decode((string) ($row['settings_json'] ?? ''), true);
        $row['settings'] = is_array($settings) ? $settings : [];
        if ($row['widget_type'] === 'gauge') {
            $row['settings'] = array_merge(self::defaultGaugeSettings(), $row['settings']);
        } elseif ($row['widget_type'] === 'number') {
            $row['settings'] = array_merge(self::defaultNumberSettings(), $row['settings']);
        } elseif ($row['widget_type'] === 'bar') {
            $row['settings'] = array_merge(self::defaultBarSettings(), $row['settings']);
        } elseif ($row['widget_type'] === 'line') {
            $row['settings'] = array_merge(self::defaultLineSettings(), $row['settings']);
        } elseif ($row['widget_type'] === 'doughnut') {
            $row['settings'] = array_merge(self::defaultDoughnutSettings(), $row['settings']);
        } elseif ($row['widget_type'] === 'table') {
            $row['settings'] = array_merge(self::defaultTableSettings(), $row['settings']);
        }
        return $row;
    }
}
