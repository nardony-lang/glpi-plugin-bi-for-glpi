(() => {
    'use strict';

    const palette = ['#206bc4', '#2fb344', '#f59f00', '#d63939', '#6f42c1', '#0ca678', '#ae3ec9', '#f76707'];

    function themeColors() {
        const styles = getComputedStyle(document.documentElement);
        return {
            text: styles.getPropertyValue('--tblr-body-color').trim() || '#182433',
            secondary: styles.getPropertyValue('--tblr-secondary-color').trim() || '#626976',
            grid: styles.getPropertyValue('--tblr-border-color').trim() || '#dce1e7',
            background: styles.getPropertyValue('--tblr-bg-surface').trim() || '#ffffff',
        };
    }

    function formatValue(value, settings = {}) {
        const numeric = Number(value);
        if (!Number.isFinite(numeric)) return String(value ?? '');
        const decimals = Number(settings.decimals);
        const options = Number.isInteger(decimals) && decimals >= 0
            ? {minimumFractionDigits: decimals, maximumFractionDigits: decimals}
            : {maximumFractionDigits: 2};
        return `${new Intl.NumberFormat('pt-BR', options).format(numeric)}${String(settings.unit || '')}`;
    }

    function toolbox() {
        return {
            right: 8,
            top: 0,
            feature: {
                saveAsImage: {
                    name: 'bi-for-glpi',
                    pixelRatio: 2,
                    title: 'Salvar como imagem',
                    backgroundColor: themeColors().background,
                },
            },
        };
    }

    function categoryAxis(labels, theme, horizontal = false) {
        return {
            type: 'category',
            data: labels,
            axisLine: {lineStyle: {color: theme.grid}},
            axisTick: {show: false},
            axisLabel: {
                color: theme.secondary,
                interval: 0,
                overflow: 'truncate',
                width: horizontal ? 190 : 86,
            },
        };
    }

    function valueAxis(settings, theme, showGrid, horizontal = false, compact = false) {
        return {
            type: 'value',
            axisLine: {show: false},
            axisTick: {show: false},
            axisLabel: {
                color: theme.secondary,
                hideOverlap: true,
                formatter: (value) => formatValue(value, settings),
            },
            splitLine: {
                show: Boolean(showGrid),
                lineStyle: {color: theme.grid, type: 'dashed'},
            },
            splitNumber: compact ? 3 : (horizontal ? 4 : 5),
        };
    }

    function barOption(data, theme, compact) {
        const settings = Object.assign({orientation: 'vertical', color: '#206bc4', use_palette: 0, show_values: 1, show_grid: 1, decimals: -1, unit: ''}, data.settings || {});
        const horizontal = settings.orientation === 'horizontal';
        const category = categoryAxis(data.labels, theme, horizontal);
        const value = valueAxis(settings, theme, settings.show_grid, horizontal, compact);
        const option = {
            aria: {enabled: true},
            animationDuration: 450,
            color: palette,
            grid: horizontal
                ? {left: '26%', right: settings.show_values ? 88 : 28, top: 42, bottom: 38, containLabel: false}
                : {left: 20, right: 24, top: 48, bottom: 20, containLabel: true},
            textStyle: {color: theme.text, fontFamily: 'system-ui, sans-serif'},
            tooltip: {
                trigger: 'axis',
                axisPointer: {type: 'shadow'},
                valueFormatter: (valueItem) => formatValue(valueItem, settings),
            },
            toolbox: toolbox(),
            series: [{
                type: 'bar',
                data: data.values,
                barMaxWidth: horizontal ? 34 : 58,
                itemStyle: {
                    borderRadius: horizontal ? [0, 5, 5, 0] : [5, 5, 0, 0],
                    color: settings.use_palette
                        ? (params) => palette[params.dataIndex % palette.length]
                        : settings.color,
                },
                label: {
                    show: Boolean(settings.show_values),
                    color: theme.text,
                    fontWeight: 600,
                    position: horizontal ? 'right' : 'top',
                    formatter: (params) => formatValue(params.value, settings),
                },
                emphasis: {focus: 'series'},
            }],
        };
        option.xAxis = horizontal ? value : category;
        option.yAxis = horizontal ? category : value;
        return option;
    }

    function lineOption(data, theme) {
        const settings = Object.assign({color: '#206bc4', show_values: 0, show_grid: 1, show_points: 1, fill_area: 1, smooth: 1, decimals: -1, unit: ''}, data.settings || {});
        const manyPoints = data.labels.length > 12;
        return {
            aria: {enabled: true},
            animationDuration: 450,
            color: [settings.color],
            grid: {left: 20, right: 24, top: 48, bottom: manyPoints ? 62 : 20, containLabel: true},
            textStyle: {color: theme.text, fontFamily: 'system-ui, sans-serif'},
            tooltip: {
                trigger: 'axis',
                valueFormatter: (valueItem) => formatValue(valueItem, settings),
            },
            toolbox: toolbox(),
            xAxis: categoryAxis(data.labels, theme),
            yAxis: valueAxis(settings, theme, settings.show_grid),
            dataZoom: manyPoints
                ? [{type: 'inside', xAxisIndex: 0}, {type: 'slider', xAxisIndex: 0, height: 18, bottom: 5}]
                : [],
            series: [{
                type: 'line',
                data: data.values,
                smooth: Boolean(settings.smooth),
                showSymbol: Boolean(settings.show_points),
                symbol: 'circle',
                symbolSize: 8,
                lineStyle: {width: 3, color: settings.color},
                itemStyle: {color: settings.color, borderColor: theme.background, borderWidth: 2},
                areaStyle: settings.fill_area ? {color: settings.color, opacity: 0.14} : undefined,
                label: {
                    show: Boolean(settings.show_values),
                    color: theme.text,
                    fontWeight: 600,
                    position: 'top',
                    formatter: (params) => formatValue(params.value, settings),
                },
                emphasis: {focus: 'series'},
            }],
        };
    }

    function doughnutOption(data, theme, compact) {
        const settings = Object.assign({legend_position: 'right', hole_size: 52, show_labels: 1, show_percentages: 1, decimals: 0, unit: ''}, data.settings || {});
        const items = data.labels.map((label, index) => ({name: String(label), value: data.values[index]}));
        const legendVisible = settings.legend_position !== 'hidden';
        const legendAtBottom = compact || settings.legend_position === 'bottom';
        const outerRadius = compact ? 68 : (legendVisible && !legendAtBottom ? 76 : 80);
        const innerRadius = Math.min(Number(settings.hole_size) || 52, outerRadius - 8);
        const percentage = (value) => formatValue(value, {decimals: settings.decimals, unit: '%'});
        const itemLabel = (params) => settings.show_percentages
            ? `${params.name}: ${percentage(params.percent)}`
            : `${params.name}: ${formatValue(params.value, settings)}`;
        return {
            aria: {enabled: true},
            animationDuration: 450,
            color: palette,
            textStyle: {color: theme.text, fontFamily: 'system-ui, sans-serif'},
            tooltip: {
                trigger: 'item',
                formatter: (params) => `${params.name}: ${formatValue(params.value, settings)} (${percentage(params.percent)})`,
            },
            toolbox: toolbox(),
            legend: legendAtBottom
                ? {show: legendVisible, type: 'scroll', bottom: 0, left: 'center', textStyle: {color: theme.secondary}}
                : {show: legendVisible, type: 'scroll', orient: 'vertical', right: 8, top: 42, bottom: 18, textStyle: {color: theme.secondary}},
            series: [{
                type: 'pie',
                radius: [`${innerRadius}%`, `${outerRadius}%`],
                center: legendAtBottom ? ['50%', '44%'] : (legendVisible ? ['38%', '53%'] : ['50%', '53%']),
                data: items,
                minAngle: 2,
                avoidLabelOverlap: true,
                itemStyle: {borderColor: theme.background, borderWidth: 2, borderRadius: 4},
                label: {show: Boolean(settings.show_labels) && !compact, color: theme.text, formatter: itemLabel},
                labelLine: {show: Boolean(settings.show_labels) && !compact},
                emphasis: {scaleSize: 8},
            }],
        };
    }

    function gaugeOption(data, theme) {
        const min = Number(data.min), max = Number(data.max), warning = Number(data.warning), success = Number(data.success);
        const range = max - min || 1;
        const unit = String(data.unit || '');
        const bands = [
            [Math.max(0, Math.min(1, (warning - min) / range)), data.color_low],
            [Math.max(0, Math.min(1, (success - min) / range)), data.color_mid],
            [1, data.color_high],
        ];
        const mainSeries = {
            type: 'gauge',
            min,
            max,
            startAngle: 205,
            endAngle: -25,
            center: ['50%', '58%'],
            radius: '86%',
            splitNumber: 5,
            axisLine: {lineStyle: {width: 24, color: bands}},
            progress: {show: false},
            pointer: {length: '62%', width: 6, itemStyle: {color: theme.text}},
            anchor: {show: true, size: 12, itemStyle: {color: theme.text}},
            axisTick: {distance: -30, length: 5, lineStyle: {color: theme.background, width: 1}},
            splitLine: {distance: -32, length: 10, lineStyle: {color: theme.background, width: 2}},
            axisLabel: {
                distance: 34,
                color: theme.secondary,
                formatter: (value) => value === min || value === max ? formatValue(value, {decimals: -1, unit: ''}) : '',
            },
            detail: {
                valueAnimation: true,
                offsetCenter: [0, '55%'],
                color: theme.text,
                fontSize: 30,
                fontWeight: 700,
                formatter: (value) => `${formatValue(value, {decimals: -1, unit})}`,
            },
            title: {show: false},
            data: [{value: Number(data.value)}],
        };
        const series = [mainSeries];
        if (data.target !== null && Number.isFinite(Number(data.target))) {
            series.push({
                type: 'gauge',
                min,
                max,
                startAngle: 205,
                endAngle: -25,
                center: ['50%', '58%'],
                radius: '86%',
                silent: true,
                axisLine: {show: false},
                axisTick: {show: false},
                splitLine: {show: false},
                axisLabel: {show: false},
                pointer: {show: true, length: '91%', width: 3, itemStyle: {color: theme.text}},
                anchor: {show: false},
                detail: {show: false},
                title: {show: true, offsetCenter: [0, '79%'], color: theme.secondary, fontSize: 12},
                data: [{value: Number(data.target), name: `Meta: ${formatValue(data.target, {decimals: -1, unit})}`}],
            });
        }
        return {
            aria: {enabled: true},
            animationDuration: 500,
            textStyle: {color: theme.text, fontFamily: 'system-ui, sans-serif'},
            tooltip: {formatter: (params) => `${params.name || 'Valor'}: ${formatValue(params.value, {decimals: -1, unit})}`},
            toolbox: toolbox(),
            series,
        };
    }

    function optionFor(type, data, container) {
        const theme = themeColors();
        if (type === 'gauge') return gaugeOption(data, theme);
        if (type === 'doughnut') return doughnutOption(data, theme, container.clientWidth < 560);
        if (type === 'line') return lineOption(data, theme);
        return barOption(data, theme, container.clientWidth < 480);
    }

    function renderEChart(container) {
        let data;
        try {
            data = JSON.parse(container.dataset.chart);
        } catch (error) {
            return;
        }
        const type = container.dataset.chartType || 'bar';
        const originalCanvas = container.querySelector('canvas');
        const label = originalCanvas ? originalCanvas.getAttribute('aria-label') : '';
        const horizontalRows = type === 'bar' && data.settings && data.settings.orientation === 'horizontal'
            ? data.values.length
            : 0;
        container.style.height = `${horizontalRows ? Math.min(660, Math.max(300, horizontalRows * 44 + 64)) : 320}px`;
        container.replaceChildren();
        container.setAttribute('role', 'img');
        if (label) container.setAttribute('aria-label', label);
        const chart = window.echarts.init(container, null, {renderer: 'canvas'});
        chart.setOption(optionFor(type, data, container));
        if ('ResizeObserver' in window) {
            new ResizeObserver(() => chart.resize()).observe(container);
        } else {
            window.addEventListener('resize', () => chart.resize());
        }
    }

    if (!window.echarts) {
        if (window.BiforglpiCanvasCharts) window.BiforglpiCanvasCharts.renderAll();
        return;
    }

    document.querySelectorAll('.biforglpi-chart').forEach((container) => {
        try {
            renderEChart(container);
        } catch (error) {
            if (window.console) console.warn('BI for GLPI: ECharts indisponível, usando o renderizador Canvas.', error);
            const canvas = document.createElement('canvas');
            canvas.setAttribute('role', 'img');
            canvas.setAttribute('aria-label', container.getAttribute('aria-label') || 'Gráfico');
            container.replaceChildren(canvas);
            if (window.BiforglpiCanvasCharts) window.BiforglpiCanvasCharts.render(container);
        }
    });
})();
