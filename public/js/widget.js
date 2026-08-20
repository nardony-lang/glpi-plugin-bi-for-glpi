(() => {
    'use strict';

    const type = document.querySelector('#widget-type');
    const gaugeSettings = document.querySelector('#biforglpi-gauge-settings');
    const numberSettings = document.querySelector('#biforglpi-number-settings');
    const barSettings = document.querySelector('#biforglpi-bar-settings');
    const lineSettings = document.querySelector('#biforglpi-line-settings');
    if (!type || !gaugeSettings || !numberSettings || !barSettings || !lineSettings) return;

    const update = () => {
        [
            [gaugeSettings, 'gauge'],
            [numberSettings, 'number'],
            [barSettings, 'bar'],
            [lineSettings, 'line'],
        ].forEach(([settings, expectedType]) => {
            const visible = type.value === expectedType;
            settings.hidden = !visible;
            settings.classList.toggle('d-none', !visible);
            settings.querySelectorAll('input, select').forEach((input) => {
                input.disabled = !visible;
            });
        });
    };

    if (window.jQuery) {
        window.jQuery(type).on('change.biforglpiWidget', update);
    } else {
        type.addEventListener('change', update);
    }
    update();
})();
