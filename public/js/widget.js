(() => {
    'use strict';

    const type = document.querySelector('#widget-type');
    const settings = document.querySelector('#biforglpi-gauge-settings');
    if (!type || !settings) return;

    const update = () => {
        const visible = type.value === 'gauge';
        settings.hidden = !visible;
        settings.querySelectorAll('input').forEach((input) => {
            input.disabled = !visible;
        });
    };

    type.addEventListener('change', update);
    update();
})();
