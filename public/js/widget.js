(() => {
    'use strict';

    const type = document.querySelector('#widget-type');
    const gaugeSettings = document.querySelector('#biforglpi-gauge-settings');
    const numberSettings = document.querySelector('#biforglpi-number-settings');
    if (!type || !gaugeSettings || !numberSettings) return;

    const update = () => {
        [[gaugeSettings, 'gauge'], [numberSettings, 'number']].forEach(([settings, expectedType]) => {
            const visible = type.value === expectedType;
            settings.hidden = !visible;
            settings.querySelectorAll('input, select').forEach((input) => {
                input.disabled = !visible;
            });
        });
    };

    type.addEventListener('change', update);
    update();
})();
