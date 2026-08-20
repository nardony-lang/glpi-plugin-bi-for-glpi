(() => {
    'use strict';

    const type = document.querySelector('#widget-type');
    const gaugeSettings = document.querySelector('#biforglpi-gauge-settings');
    const numberSettings = document.querySelector('#biforglpi-number-settings');
    const barSettings = document.querySelector('#biforglpi-bar-settings');
    const lineSettings = document.querySelector('#biforglpi-line-settings');
    const doughnutSettings = document.querySelector('#biforglpi-doughnut-settings');
    const tableSettings = document.querySelector('#biforglpi-table-settings');
    const tableColumns = document.querySelector('#biforglpi-table-columns');
    const tableColumnTemplate = document.querySelector('#biforglpi-table-column-template');
    const addTableColumn = document.querySelector('#biforglpi-add-table-column');
    if (!type || !gaugeSettings || !numberSettings || !barSettings || !lineSettings || !doughnutSettings || !tableSettings) return;

    const appendTableColumn = () => {
        if (!tableColumns || !tableColumnTemplate) return;
        tableColumns.append(tableColumnTemplate.content.cloneNode(true));
    };

    const update = () => {
        [
            [gaugeSettings, 'gauge'],
            [numberSettings, 'number'],
            [barSettings, 'bar'],
            [lineSettings, 'line'],
            [doughnutSettings, 'doughnut'],
            [tableSettings, 'table'],
        ].forEach(([settings, expectedType]) => {
            const visible = type.value === expectedType;
            settings.hidden = !visible;
            settings.classList.toggle('d-none', !visible);
            settings.querySelectorAll('input, select').forEach((input) => {
                input.disabled = !visible;
            });
        });
        if (type.value === 'table' && tableColumns && !tableColumns.querySelector('[data-table-column-row]')) {
            appendTableColumn();
        }
    };

    if (addTableColumn) addTableColumn.addEventListener('click', appendTableColumn);
    if (tableColumns) {
        tableColumns.addEventListener('click', (event) => {
            const button = event.target.closest('button');
            const row = event.target.closest('[data-table-column-row]');
            if (!button || !row) return;
            if (button.matches('[data-column-remove]')) row.remove();
            if (button.matches('[data-column-up]') && row.previousElementSibling) {
                tableColumns.insertBefore(row, row.previousElementSibling);
            }
        });
    }

    if (window.jQuery) {
        window.jQuery(type).on('change.biforglpiWidget', update);
    } else {
        type.addEventListener('change', update);
    }
    update();
})();
