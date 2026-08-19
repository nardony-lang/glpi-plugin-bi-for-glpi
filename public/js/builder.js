(() => {
    'use strict';

    const grid = document.querySelector('#biforglpi-builder-grid');
    const form = document.querySelector('#biforglpi-layout-form');
    if (!grid || !form) return;

    let dragged = null;
    let dragArmed = null;

    const items = () => [...grid.querySelectorAll('.biforglpi-builder-item')];
    const updateButtons = () => {
        const current = items();
        current.forEach((item, index) => {
            item.querySelector('.biforglpi-move-up').disabled = index === 0;
            item.querySelector('.biforglpi-move-down').disabled = index === current.length - 1;
        });
    };

    const move = (item, direction) => {
        const sibling = direction < 0 ? item.previousElementSibling : item.nextElementSibling;
        if (!sibling) return;
        if (direction < 0) grid.insertBefore(item, sibling);
        else grid.insertBefore(sibling, item);
        updateButtons();
        item.querySelector('.biforglpi-drag-handle').focus();
    };

    grid.addEventListener('click', (event) => {
        const item = event.target.closest('.biforglpi-builder-item');
        if (!item) return;
        if (event.target.closest('.biforglpi-move-up')) move(item, -1);
        if (event.target.closest('.biforglpi-move-down')) move(item, 1);
        const deleteButton = event.target.closest('.biforglpi-delete-widget');
        if (deleteButton && !window.confirm(`Excluir o componente “${deleteButton.dataset.widgetTitle}”?`)) {
            event.preventDefault();
        }
    });

    grid.addEventListener('change', (event) => {
        if (!event.target.matches('.biforglpi-builder-width')) return;
        const item = event.target.closest('.biforglpi-builder-item');
        item.style.setProperty('--biforglpi-builder-width', event.target.value);
    });

    grid.addEventListener('pointerdown', (event) => {
        dragArmed = event.target.closest('.biforglpi-drag-handle')
            ? event.target.closest('.biforglpi-builder-item')
            : null;
    });

    grid.addEventListener('dragstart', (event) => {
        const item = event.target.closest('.biforglpi-builder-item');
        if (!item || dragArmed !== item) {
            event.preventDefault();
            return;
        }
        dragged = item;
        item.classList.add('biforglpi-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', item.dataset.widgetId || '');
    });

    grid.addEventListener('dragover', (event) => {
        if (!dragged) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        const target = event.target.closest('.biforglpi-builder-item');
        if (!target || target === dragged) return;
        const bounds = target.getBoundingClientRect();
        const centerY = bounds.top + bounds.height / 2;
        const useHorizontalPosition = Math.abs(event.clientY - centerY) < bounds.height / 4;
        const before = useHorizontalPosition
            ? event.clientX < bounds.left + bounds.width / 2
            : event.clientY < centerY;
        grid.insertBefore(dragged, before ? target : target.nextElementSibling);
    });

    grid.addEventListener('dragend', () => {
        if (dragged) dragged.classList.remove('biforglpi-dragging');
        dragged = null;
        dragArmed = null;
        updateButtons();
    });

    updateButtons();
})();
