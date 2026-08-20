(() => {
    'use strict';

    const svgNamespace = 'http://www.w3.org/2000/svg';

    function svgElement(name, attributes = {}) {
        const element = document.createElementNS(svgNamespace, name);
        Object.entries(attributes).forEach(([key, value]) => element.setAttribute(key, String(value)));
        return element;
    }

    function sparklineValues(svg) {
        try {
            const values = JSON.parse(svg.dataset.sparklineValues || '[]').map(Number).filter(Number.isFinite);
            return values.slice(0, 60);
        } catch (error) {
            return [];
        }
    }

    function renderLineSparkline(svg, values, color) {
        const min = Math.min(...values), max = Math.max(...values), range = max - min || 1;
        const points = values.map((value, index) => {
            const x = values.length === 1 ? 60 : 3 + (index / (values.length - 1)) * 114;
            const y = 32 - ((value - min) / range) * 28;
            return [x, y];
        });
        const line = points.map(([x, y], index) => `${index === 0 ? 'M' : 'L'}${x.toFixed(2)},${y.toFixed(2)}`).join(' ');
        const area = `${line} L${points.at(-1)[0].toFixed(2)},34 L${points[0][0].toFixed(2)},34 Z`;
        svg.append(svgElement('path', {d: area, fill: color, opacity: 0.12}));
        svg.append(svgElement('path', {d: line, fill: 'none', stroke: color, 'stroke-width': 2.5, 'stroke-linecap': 'round', 'stroke-linejoin': 'round'}));
        const [lastX, lastY] = points.at(-1);
        svg.append(svgElement('circle', {cx: lastX, cy: lastY, r: 2.8, fill: color, stroke: '#fff', 'stroke-width': 1.2}));
    }

    function renderBarSparkline(svg, values, color) {
        const min = Math.min(0, ...values), max = Math.max(0, ...values), range = max - min || 1;
        const slot = 116 / Math.max(values.length, 1), barWidth = Math.max(2, Math.min(12, slot * 0.68));
        const zeroY = 32 - ((0 - min) / range) * 28;
        values.forEach((value, index) => {
            const valueY = 32 - ((value - min) / range) * 28;
            const x = 2 + index * slot + (slot - barWidth) / 2;
            svg.append(svgElement('rect', {
                x: x.toFixed(2),
                y: Math.min(zeroY, valueY).toFixed(2),
                width: barWidth.toFixed(2),
                height: Math.max(1, Math.abs(zeroY - valueY)).toFixed(2),
                rx: 1.5,
                fill: color,
            }));
        });
    }

    function renderSparklines(root = document) {
        root.querySelectorAll('.biforglpi-sparkline').forEach((svg) => {
            const values = sparklineValues(svg);
            svg.replaceChildren();
            if (values.length === 0) return;
            const color = /^#[0-9a-f]{6}$/i.test(svg.dataset.sparklineColor || '') ? svg.dataset.sparklineColor : '#206bc4';
            if (svg.dataset.sparklineType === 'bar') renderBarSparkline(svg, values, color);
            else renderLineSparkline(svg, values, color);
        });
    }

    function filename(value, extension) {
        const safe = String(value || 'tabela')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9_-]+/gi, '-')
            .replace(/^-+|-+$/g, '')
            .toLowerCase() || 'tabela';
        return `${safe}.${extension}`;
    }

    function downloadBlob(blob, name) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = name;
        document.body.append(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    const unsupportedColorFunction = /(?:^|[\s,(])(?:color|color-mix|lab|lch|oklab|oklch)\(/i;
    const exportColorProperties = [
        'color',
        'background-color',
        'border-top-color',
        'border-right-color',
        'border-bottom-color',
        'border-left-color',
        'outline-color',
        'text-decoration-color',
        'fill',
        'stroke',
    ];

    function colorConverter(ownerDocument) {
        const canvas = ownerDocument.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        return canvas.getContext('2d', {willReadFrequently: true});
    }

    function rgbaColor(value, context, cache) {
        if (!unsupportedColorFunction.test(value)) return value;
        if (cache.has(value)) return cache.get(value);
        context.clearRect(0, 0, 1, 1);
        context.fillStyle = '#000000';
        context.fillStyle = value;
        context.fillRect(0, 0, 1, 1);
        const [red, green, blue, alpha] = context.getImageData(0, 0, 1, 1).data;
        const converted = `rgba(${red}, ${green}, ${blue}, ${(alpha / 255).toFixed(3)})`;
        cache.set(value, converted);
        return converted;
    }

    function sanitizeExportColors(root) {
        const ownerDocument = root.ownerDocument;
        const view = ownerDocument.defaultView || window;
        const context = colorConverter(ownerDocument);
        if (!context) return;
        const cache = new Map();
        root.setAttribute('data-biforglpi-export-copy', '');
        const pseudoStyle = ownerDocument.createElement('style');
        pseudoStyle.setAttribute('data-html2canvas-ignore', 'true');
        pseudoStyle.textContent = '[data-biforglpi-export-copy]::before,[data-biforglpi-export-copy]::after,'
            + '[data-biforglpi-export-copy] *::before,[data-biforglpi-export-copy] *::after{'
            + 'content:none!important;color:#000!important;background:transparent!important;'
            + 'border-color:transparent!important;box-shadow:none!important;text-shadow:none!important}';
        root.prepend(pseudoStyle);
        [root, ...root.querySelectorAll('*')].forEach((element) => {
            if (!(element instanceof view.HTMLElement) && !(element instanceof view.SVGElement)) return;
            const computed = view.getComputedStyle(element);
            exportColorProperties.forEach((property) => {
                const value = computed.getPropertyValue(property);
                if (value && unsupportedColorFunction.test(value)) {
                    element.style.setProperty(property, rgbaColor(value, context, cache), 'important');
                }
            });
            ['box-shadow', 'text-shadow', 'background-image'].forEach((property) => {
                if (unsupportedColorFunction.test(computed.getPropertyValue(property) || '')) {
                    element.style.setProperty(property, 'none', 'important');
                }
            });
        });
    }

    async function captureWidget(widget) {
        if (typeof window.html2canvas !== 'function') throw new Error('html2canvas unavailable');
        if (document.fonts && document.fonts.ready) await document.fonts.ready;
        const clone = widget.cloneNode(true);
        clone.querySelectorAll('[id]').forEach((element) => element.removeAttribute('id'));
        clone.classList.add('biforglpi-export-clone');
        const sourceTable = widget.querySelector('.biforglpi-analytics-table');
        const exportWidth = Math.max(widget.offsetWidth, sourceTable ? sourceTable.scrollWidth + 36 : 0);
        clone.style.width = `${exportWidth}px`;
        clone.querySelectorAll('.table-responsive').forEach((element) => {
            element.style.overflow = 'visible';
            element.style.maxHeight = 'none';
        });
        document.body.append(clone);
        try {
            sanitizeExportColors(clone);
            const cloneTop = clone.getBoundingClientRect().top;
            const rowOffsets = Array.from(clone.querySelectorAll('.biforglpi-analytics-table tbody tr'))
                .map((row) => row.getBoundingClientRect().bottom - cloneTop);
            const canvas = await window.html2canvas(clone, {
                backgroundColor: '#ffffff',
                scale: 2,
                logging: false,
                useCORS: false,
                width: clone.scrollWidth,
                height: clone.scrollHeight,
                windowWidth: clone.scrollWidth,
                windowHeight: clone.scrollHeight,
                onclone: (clonedDocument) => {
                    const clonedRoot = clonedDocument.querySelector('[data-biforglpi-export-copy]');
                    if (clonedRoot) sanitizeExportColors(clonedRoot);
                },
            });
            const scaleY = canvas.height / Math.max(clone.scrollHeight, 1);
            return {canvas, rowBreaks: rowOffsets.map((offset) => Math.round(offset * scaleY))};
        } finally {
            clone.remove();
        }
    }

    async function exportPng(widget) {
        const {canvas} = await captureWidget(widget);
        const blob = await new Promise((resolve, reject) => canvas.toBlob((value) => value ? resolve(value) : reject(new Error('PNG unavailable')), 'image/png'));
        downloadBlob(blob, filename(widget.dataset.exportName, 'png'));
    }

    async function exportPdf(widget) {
        if (!window.jspdf || typeof window.jspdf.jsPDF !== 'function') throw new Error('jsPDF unavailable');
        const {canvas, rowBreaks} = await captureWidget(widget);
        const orientation = canvas.width > canvas.height * 1.1 ? 'landscape' : 'portrait';
        const pdf = new window.jspdf.jsPDF({orientation, unit: 'mm', format: 'a4', compress: true});
        const margin = 8;
        const usableWidth = pdf.internal.pageSize.getWidth() - margin * 2;
        const usableHeight = pdf.internal.pageSize.getHeight() - margin * 2;
        const pixelsPerPage = Math.max(1, Math.floor(canvas.width * usableHeight / usableWidth));
        let sourceY = 0, page = 0;
        while (sourceY < canvas.height) {
            const pageLimit = Math.min(sourceY + pixelsPerPage, canvas.height);
            const safeBreaks = rowBreaks.filter((position) => position > sourceY + 40 && position <= pageLimit);
            const sliceEnd = pageLimit < canvas.height && safeBreaks.length > 0 ? safeBreaks.at(-1) : pageLimit;
            const sliceHeight = sliceEnd - sourceY;
            const slice = document.createElement('canvas');
            slice.width = canvas.width;
            slice.height = sliceHeight;
            const context = slice.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, slice.width, slice.height);
            context.drawImage(canvas, 0, sourceY, canvas.width, sliceHeight, 0, 0, canvas.width, sliceHeight);
            if (page > 0) pdf.addPage('a4', orientation);
            const imageHeight = sliceHeight * usableWidth / canvas.width;
            pdf.addImage(slice.toDataURL('image/png'), 'PNG', margin, margin, usableWidth, imageHeight, undefined, 'FAST');
            sourceY += sliceHeight;
            page++;
        }
        pdf.save(filename(widget.dataset.exportName, 'pdf'));
    }

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-table-export]');
        if (!button) return;
        const widget = button.closest('[data-table-widget]');
        if (!widget || button.disabled) return;
        const format = button.dataset.tableExport === 'pdf' ? 'pdf' : 'png';
        const original = button.innerHTML;
        button.disabled = true;
        widget.dataset.exportStatus = `${format}-running`;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Gerando…';
        try {
            if (format === 'pdf') await exportPdf(widget);
            else await exportPng(widget);
            widget.dataset.exportStatus = `${format}-ok`;
        } catch (error) {
            widget.dataset.exportStatus = `${format}-error`;
            if (window.console) console.error('BI for GLPI: falha ao exportar tabela.', error);
            window.alert('Não foi possível exportar a tabela. Atualize a página e tente novamente.');
        } finally {
            button.disabled = false;
            button.innerHTML = original;
        }
    });

    renderSparklines();
})();
