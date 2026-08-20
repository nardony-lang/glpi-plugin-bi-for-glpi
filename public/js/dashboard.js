(() => {
    'use strict';

    const colors = ['#206bc4', '#2fb344', '#f59f00', '#d63939', '#6f42c1', '#0ca678', '#ae3ec9', '#f76707'];

    function setupCanvas(canvas, requestedHeight = 300) {
        const width = Math.max(280, canvas.parentElement.clientWidth);
        const height = Math.max(280, requestedHeight);
        const ratio = window.devicePixelRatio || 1;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        const context = canvas.getContext('2d');
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        context.clearRect(0, 0, width, height);
        context.font = '12px system-ui, sans-serif';
        return {context, width, height};
    }

    function themeColors() {
        const styles = getComputedStyle(document.documentElement);
        return {
            text: styles.getPropertyValue('--tblr-body-color').trim() || '#182433',
            secondary: styles.getPropertyValue('--tblr-secondary-color').trim() || '#626976',
            grid: styles.getPropertyValue('--tblr-border-color').trim() || '#dce1e7',
        };
    }

    function rgba(hex, alpha) {
        const match = /^#([0-9a-f]{6})$/i.exec(hex);
        if (!match) return `rgba(32,107,196,${alpha})`;
        const value = Number.parseInt(match[1], 16);
        return `rgba(${value >> 16},${(value >> 8) & 255},${value & 255},${alpha})`;
    }

    function niceStep(value) {
        const exponent = Math.floor(Math.log10(Math.max(value, Number.EPSILON)));
        const fraction = value / (10 ** exponent);
        const niceFraction = fraction <= 1 ? 1 : (fraction <= 2 ? 2 : (fraction <= 5 ? 5 : 10));
        return niceFraction * (10 ** exponent);
    }

    function scaleFor(values) {
        let min = Math.min(0, ...values), max = Math.max(0, ...values);
        if (min === max) max = min + 1;
        const step = niceStep((max - min) / 5);
        min = Math.floor(min / step) * step;
        max = Math.ceil(max / step) * step;
        const ticks = [];
        for (let value = min, guard = 0; value <= max + step / 2 && guard < 10; value += step, guard += 1) {
            ticks.push(Math.abs(value) < step / 1000 ? 0 : value);
        }
        return {min, max, range: max - min || 1, ticks};
    }

    function formatValue(value, settings = {}) {
        const decimals = Number(settings.decimals);
        const options = Number.isInteger(decimals) && decimals >= 0
            ? {minimumFractionDigits: decimals, maximumFractionDigits: decimals}
            : {maximumFractionDigits: 2};
        return `${new Intl.NumberFormat('pt-BR', options).format(value)}${String(settings.unit || '')}`;
    }

    function fitText(context, text, maxWidth) {
        const value = String(text);
        if (context.measureText(value).width <= maxWidth) return value;
        let shortened = value;
        while (shortened.length > 1 && context.measureText(`${shortened}…`).width > maxWidth) shortened = shortened.slice(0, -1);
        return `${shortened}…`;
    }

    function drawVerticalBar(context, width, height, data, settings, theme) {
        const left = 66, right = 18, top = settings.show_values ? 34 : 18, bottom = 58;
        const plotWidth = Math.max(1, width - left - right), plotHeight = Math.max(1, height - top - bottom);
        const scale = scaleFor(data.values), yFor = (value) => top + plotHeight - ((value - scale.min) / scale.range) * plotHeight;
        const zeroY = yFor(0), slot = plotWidth / data.values.length;
        if (settings.show_grid) {
            context.textAlign = 'right'; context.textBaseline = 'middle'; context.font = '11px system-ui, sans-serif';
            scale.ticks.forEach((tick) => {
                const y = yFor(tick); context.strokeStyle = theme.grid; context.lineWidth = 1;
                context.beginPath(); context.moveTo(left, y); context.lineTo(width - right, y); context.stroke();
                context.fillStyle = theme.secondary; context.fillText(formatValue(tick, settings), left - 8, y);
            });
        }
        context.strokeStyle = theme.secondary; context.beginPath(); context.moveTo(left, zeroY); context.lineTo(width - right, zeroY); context.stroke();
        data.values.forEach((value, index) => {
            const x = left + index * slot + slot * 0.14, barWidth = Math.max(3, slot * 0.72), valueY = yFor(value);
            context.fillStyle = settings.use_palette ? colors[index % colors.length] : settings.color;
            context.fillRect(x, Math.min(valueY, zeroY), barWidth, Math.max(1, Math.abs(zeroY - valueY)));
            context.fillStyle = theme.secondary; context.textAlign = 'center'; context.textBaseline = 'top'; context.font = '11px system-ui, sans-serif';
            context.fillText(fitText(context, data.labels[index], Math.max(20, slot - 6)), x + barWidth / 2, height - bottom + 12, Math.max(20, slot - 6));
            if (settings.show_values) {
                context.fillStyle = theme.text; context.font = '600 11px system-ui, sans-serif'; context.textBaseline = value >= 0 ? 'bottom' : 'top';
                context.fillText(formatValue(value, settings), x + barWidth / 2, valueY + (value >= 0 ? -5 : 5), Math.max(slot + 12, 48));
            }
        });
    }

    function drawHorizontalBar(context, width, height, data, settings, theme) {
        const left = Math.min(Math.max(width * 0.3, 110), 220), right = settings.show_values ? 78 : 22, top = 18, bottom = 36;
        const plotWidth = Math.max(1, width - left - right), plotHeight = Math.max(1, height - top - bottom);
        const scale = scaleFor(data.values), xFor = (value) => left + ((value - scale.min) / scale.range) * plotWidth;
        const zeroX = xFor(0), slot = plotHeight / data.values.length;
        if (settings.show_grid) {
            context.textAlign = 'center'; context.textBaseline = 'top'; context.font = '11px system-ui, sans-serif';
            scale.ticks.forEach((tick) => {
                const x = xFor(tick); context.strokeStyle = theme.grid; context.lineWidth = 1;
                context.beginPath(); context.moveTo(x, top); context.lineTo(x, height - bottom); context.stroke();
                context.fillStyle = theme.secondary; context.fillText(formatValue(tick, settings), x, height - bottom + 9, 64);
            });
        }
        context.strokeStyle = theme.secondary; context.beginPath(); context.moveTo(zeroX, top); context.lineTo(zeroX, height - bottom); context.stroke();
        data.values.forEach((value, index) => {
            const y = top + index * slot + slot * 0.14, barHeight = Math.max(3, slot * 0.72), valueX = xFor(value);
            context.fillStyle = settings.use_palette ? colors[index % colors.length] : settings.color;
            context.fillRect(Math.min(valueX, zeroX), y, Math.max(1, Math.abs(zeroX - valueX)), barHeight);
            context.fillStyle = theme.secondary; context.textAlign = 'right'; context.textBaseline = 'middle'; context.font = '11px system-ui, sans-serif';
            context.fillText(fitText(context, data.labels[index], left - 20), left - 10, y + barHeight / 2, left - 20);
            if (settings.show_values) {
                context.fillStyle = theme.text; context.font = '600 11px system-ui, sans-serif'; context.textAlign = value >= 0 ? 'left' : 'right';
                context.fillText(formatValue(value, settings), valueX + (value >= 0 ? 7 : -7), y + barHeight / 2, right - 10);
            }
        });
    }

    function drawBar(context, width, height, data) {
        const settings = Object.assign({orientation: 'vertical', color: '#206bc4', use_palette: 0, show_values: 1, show_grid: 1, decimals: -1, unit: ''}, data.settings || {});
        const theme = themeColors();
        if (settings.orientation === 'horizontal') drawHorizontalBar(context, width, height, data, settings, theme);
        else drawVerticalBar(context, width, height, data, settings, theme);
    }

    function traceLine(context, points, smooth) {
        if (points.length === 0) return;
        context.moveTo(points[0].x, points[0].y);
        for (let index = 1; index < points.length; index += 1) {
            const previous = points[index - 1], point = points[index], control = (point.x - previous.x) / 3;
            if (smooth) context.bezierCurveTo(previous.x + control, previous.y, point.x - control, point.y, point.x, point.y);
            else context.lineTo(point.x, point.y);
        }
    }

    function drawLine(context, width, height, data) {
        const settings = Object.assign({color: '#206bc4', show_values: 0, show_grid: 1, show_points: 1, fill_area: 1, smooth: 1, decimals: -1, unit: ''}, data.settings || {});
        const theme = themeColors(), left = 66, right = 20, top = settings.show_values ? 38 : 22, bottom = 58;
        const plotWidth = Math.max(1, width - left - right), plotHeight = Math.max(1, height - top - bottom);
        const scale = scaleFor(data.values), yFor = (value) => top + plotHeight - ((value - scale.min) / scale.range) * plotHeight;
        const points = data.values.map((value, index) => ({
            x: left + (data.values.length === 1 ? plotWidth / 2 : index * plotWidth / (data.values.length - 1)),
            y: yFor(value), value,
        }));
        if (settings.show_grid) {
            context.textAlign = 'right'; context.textBaseline = 'middle'; context.font = '11px system-ui, sans-serif';
            scale.ticks.forEach((tick) => {
                const y = yFor(tick); context.strokeStyle = theme.grid; context.lineWidth = 1;
                context.beginPath(); context.moveTo(left, y); context.lineTo(width - right, y); context.stroke();
                context.fillStyle = theme.secondary; context.fillText(formatValue(tick, settings), left - 8, y);
            });
        }
        const zeroY = yFor(Math.max(scale.min, Math.min(scale.max, 0)));
        if (settings.fill_area && points.length > 1) {
            context.beginPath(); traceLine(context, points, settings.smooth); context.lineTo(points[points.length - 1].x, zeroY); context.lineTo(points[0].x, zeroY); context.closePath();
            context.fillStyle = rgba(settings.color, 0.14); context.fill();
        }
        context.beginPath(); traceLine(context, points, settings.smooth); context.strokeStyle = settings.color; context.lineWidth = 3; context.lineJoin = 'round'; context.lineCap = 'round'; context.stroke();
        const labelEvery = Math.max(1, Math.ceil(data.labels.length / Math.max(1, Math.floor(plotWidth / 70))));
        points.forEach((point, index) => {
            if (settings.show_points) { context.beginPath(); context.arc(point.x, point.y, 4, 0, Math.PI * 2); context.fillStyle = settings.color; context.fill(); context.strokeStyle = '#ffffff'; context.lineWidth = 2; context.stroke(); }
            if (index % labelEvery === 0 || index === points.length - 1) { context.fillStyle = theme.secondary; context.textAlign = 'center'; context.textBaseline = 'top'; context.font = '11px system-ui, sans-serif'; context.fillText(fitText(context, data.labels[index], 68), point.x, height - bottom + 13, 68); }
            if (settings.show_values) { context.fillStyle = theme.text; context.textAlign = 'center'; context.textBaseline = 'bottom'; context.font = '600 11px system-ui, sans-serif'; context.fillText(formatValue(point.value, settings), point.x, point.y - 8, 70); }
        });
    }

    function drawDoughnut(context, width, height, data) {
        const total = data.values.reduce((sum, value) => sum + Math.max(value, 0), 0) || 1;
        const centerX = Math.min(width * 0.36, 180), centerY = height / 2, radius = Math.min(100, width * 0.25), inner = radius * 0.56;
        let angle = -Math.PI / 2;
        data.values.forEach((value, index) => {
            const next = angle + (Math.max(value, 0) / total) * Math.PI * 2;
            context.beginPath(); context.arc(centerX, centerY, radius, angle, next); context.arc(centerX, centerY, inner, next, angle, true); context.closePath(); context.fillStyle = colors[index % colors.length]; context.fill(); angle = next;
        });
        const legendX = centerX + radius + 24;
        data.labels.slice(0, 8).forEach((label, index) => { context.fillStyle = colors[index % colors.length]; context.fillRect(legendX, 34 + index * 27, 12, 12); context.fillStyle = '#182433'; context.textAlign = 'left'; context.fillText(`${String(label).slice(0, 20)}: ${data.values[index]}`, legendX + 20, 44 + index * 27, Math.max(width - legendX - 24, 80)); });
    }

    function drawGauge(context, width, height, data) {
        const min = Number(data.min), max = Number(data.max), value = Number(data.value);
        if (![min, max, value].every(Number.isFinite) || max <= min) return;
        const centerX = width / 2, centerY = height * 0.7, radius = Math.min(105, width * 0.34);
        const start = Math.PI, range = max - min;
        const angleFor = (number) => start + Math.max(0, Math.min(1, (number - min) / range)) * Math.PI;
        const arc = (from, to, color, lineWidth) => {
            context.beginPath(); context.arc(centerX, centerY, radius, angleFor(from), angleFor(to));
            context.strokeStyle = color; context.lineWidth = lineWidth; context.lineCap = 'butt'; context.stroke();
        };
        arc(min, max, '#e9ecef', 26);
        arc(min, Number(data.warning), data.color_low, 26);
        arc(Number(data.warning), Number(data.success), data.color_mid, 26);
        arc(Number(data.success), max, data.color_high, 26);

        if (data.target !== null && Number.isFinite(Number(data.target))) {
            const targetAngle = angleFor(Number(data.target));
            context.beginPath();
            context.moveTo(centerX + Math.cos(targetAngle) * (radius - 20), centerY + Math.sin(targetAngle) * (radius - 20));
            context.lineTo(centerX + Math.cos(targetAngle) * (radius + 20), centerY + Math.sin(targetAngle) * (radius + 20));
            context.strokeStyle = '#182433'; context.lineWidth = 3; context.stroke();
        }

        const needleAngle = angleFor(value);
        context.beginPath(); context.moveTo(centerX, centerY);
        context.lineTo(centerX + Math.cos(needleAngle) * (radius - 18), centerY + Math.sin(needleAngle) * (radius - 18));
        context.strokeStyle = '#182433'; context.lineWidth = 4; context.lineCap = 'round'; context.stroke();
        context.beginPath(); context.arc(centerX, centerY, 8, 0, Math.PI * 2); context.fillStyle = '#182433'; context.fill();

        const formatted = new Intl.NumberFormat('pt-BR', {maximumFractionDigits: 2}).format(value);
        const unit = String(data.unit || '');
        context.fillStyle = '#182433'; context.font = 'bold 30px sans-serif'; context.textAlign = 'center';
        context.fillText(`${formatted}${unit === '%' ? '' : ' '}${unit}`, centerX, centerY + 48);
        context.fillStyle = '#626976'; context.font = '12px sans-serif';
        context.textAlign = 'left'; context.fillText(String(min), centerX - radius - 12, centerY + 24);
        context.textAlign = 'right'; context.fillText(String(max), centerX + radius + 12, centerY + 24);
        if (data.target !== null) {
            context.textAlign = 'center'; context.fillText(`Meta: ${data.target}${unit}`, centerX, centerY + 70);
        }
    }

    function render(container) {
        let data;
        try { data = JSON.parse(container.dataset.chart); } catch (error) { return; }
        const canvas = container.querySelector('canvas');
        const requestedHeight = container.dataset.chartType === 'bar' && data.settings && data.settings.orientation === 'horizontal'
            ? Math.min(620, Math.max(300, data.values.length * 42 + 54))
            : 300;
        const {context, width, height} = setupCanvas(canvas, requestedHeight);
        if (container.dataset.chartType === 'gauge') drawGauge(context, width, height, data);
        else if (container.dataset.chartType === 'doughnut') drawDoughnut(context, width, height, data);
        else if (container.dataset.chartType === 'line') drawLine(context, width, height, data);
        else drawBar(context, width, height, data);
    }

    document.querySelectorAll('.biforglpi-chart').forEach((container) => {
        render(container);
        if ('ResizeObserver' in window) new ResizeObserver(() => render(container)).observe(container);
    });
})();
