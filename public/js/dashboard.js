(() => {
    'use strict';

    const colors = ['#206bc4', '#2fb344', '#f59f00', '#d63939', '#6f42c1', '#0ca678', '#ae3ec9', '#f76707'];

    function setupCanvas(canvas) {
        const width = Math.max(280, canvas.parentElement.clientWidth);
        const height = 280;
        const ratio = window.devicePixelRatio || 1;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        const context = canvas.getContext('2d');
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        context.clearRect(0, 0, width, height);
        context.font = '12px sans-serif';
        return {context, width, height};
    }

    function drawBar(context, width, height, data) {
        const max = Math.max(...data.values, 1);
        const left = 42, top = 16, bottom = 45, plotWidth = width - left - 12, plotHeight = height - top - bottom;
        const slot = plotWidth / data.values.length;
        context.strokeStyle = '#dce1e7';
        context.beginPath(); context.moveTo(left, top); context.lineTo(left, top + plotHeight); context.lineTo(width - 12, top + plotHeight); context.stroke();
        data.values.forEach((value, index) => {
            const barHeight = (value / max) * (plotHeight - 8);
            context.fillStyle = colors[index % colors.length];
            context.fillRect(left + index * slot + slot * 0.16, top + plotHeight - barHeight, slot * 0.68, barHeight);
            context.fillStyle = '#626976';
            context.textAlign = 'center';
            context.fillText(String(data.labels[index]).slice(0, 12), left + index * slot + slot / 2, height - 18, Math.max(slot - 4, 20));
        });
    }

    function drawLine(context, width, height, data) {
        const max = Math.max(...data.values, 1), min = Math.min(...data.values, 0);
        const left = 42, top = 16, bottom = 45, plotWidth = width - left - 12, plotHeight = height - top - bottom;
        const range = max - min || 1;
        const points = data.values.map((value, index) => ({
            x: left + (data.values.length === 1 ? plotWidth / 2 : index * plotWidth / (data.values.length - 1)),
            y: top + plotHeight - ((value - min) / range) * (plotHeight - 8),
        }));
        context.strokeStyle = '#206bc4'; context.lineWidth = 3; context.beginPath();
        points.forEach((point, index) => index ? context.lineTo(point.x, point.y) : context.moveTo(point.x, point.y));
        context.stroke();
        points.forEach((point, index) => { context.fillStyle = '#206bc4'; context.beginPath(); context.arc(point.x, point.y, 4, 0, Math.PI * 2); context.fill(); context.fillStyle = '#626976'; context.textAlign = 'center'; context.fillText(String(data.labels[index]).slice(0, 12), point.x, height - 18, 70); });
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

    function render(container) {
        let data;
        try { data = JSON.parse(container.dataset.chart); } catch (error) { return; }
        const canvas = container.querySelector('canvas');
        const {context, width, height} = setupCanvas(canvas);
        if (container.dataset.chartType === 'doughnut') drawDoughnut(context, width, height, data);
        else if (container.dataset.chartType === 'line') drawLine(context, width, height, data);
        else drawBar(context, width, height, data);
    }

    document.querySelectorAll('.biforglpi-chart').forEach((container) => {
        render(container);
        if ('ResizeObserver' in window) new ResizeObserver(() => render(container)).observe(container);
    });
})();
