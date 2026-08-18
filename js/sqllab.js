(() => {
    'use strict';

    const lab = document.querySelector('.biforglpi-lab');
    const form = document.querySelector('#biforglpi-query-form');
    if (!lab || !form) {
        return;
    }

    const button = document.querySelector('#biforglpi-run');
    const resultCard = document.querySelector('#biforglpi-result-card');
    const errorBox = document.querySelector('#biforglpi-error');
    const summary = document.querySelector('#biforglpi-summary');
    const tableWrap = document.querySelector('#biforglpi-table-wrap');
    const csrfInput = document.querySelector('#biforglpi-csrf-token');
    const csrfMeta = document.querySelector('meta[property="glpi:csrf_token"]');

    if (csrfInput && csrfMeta) {
        csrfInput.value = csrfMeta.getAttribute('content') || '';
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setLoading(true);
        showResult();
        clearResult();

        try {
            const response = await fetch(lab.dataset.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Glpi-Csrf-Token': csrfInput ? csrfInput.value : '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams(new FormData(form)),
            });

            const payload = await response.json().catch(() => ({
                ok: false,
                message: 'O servidor retornou uma resposta inválida.',
            }));

            if (!response.ok || !payload.ok) {
                throw new Error(payload.message || 'Não foi possível executar a consulta.');
            }

            renderTable(payload.columns, payload.rows);
            const suffix = payload.truncated
                ? ` — resultado limitado a ${payload.limit} linhas`
                : '';
            summary.textContent = `${payload.rows.length} linha(s) em ${payload.elapsed_ms} ms${suffix}`;
        } catch (error) {
            errorBox.textContent = error instanceof Error
                ? error.message
                : 'Não foi possível executar a consulta.';
            errorBox.hidden = false;
        } finally {
            setLoading(false);
        }
    });

    function setLoading(isLoading) {
        button.disabled = isLoading;
        button.setAttribute('aria-busy', String(isLoading));
        button.querySelector('i').className = isLoading
            ? 'ti ti-loader-2 ti-spin'
            : 'ti ti-player-play';
    }

    function showResult() {
        resultCard.hidden = false;
        resultCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function clearResult() {
        errorBox.hidden = true;
        errorBox.textContent = '';
        summary.textContent = '';
        tableWrap.replaceChildren();
    }

    function renderTable(columns, rows) {
        const table = document.createElement('table');
        table.className = 'table table-vcenter table-striped table-hover';

        const head = document.createElement('thead');
        const headRow = document.createElement('tr');
        columns.forEach((column) => {
            const cell = document.createElement('th');
            cell.scope = 'col';
            cell.textContent = column;
            headRow.appendChild(cell);
        });
        head.appendChild(headRow);
        table.appendChild(head);

        const body = document.createElement('tbody');
        if (rows.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = Math.max(1, columns.length);
            cell.className = 'text-center text-secondary py-4';
            cell.textContent = 'A consulta não retornou linhas.';
            row.appendChild(cell);
            body.appendChild(row);
        } else {
            rows.forEach((resultRow) => {
                const row = document.createElement('tr');
                columns.forEach((column) => {
                    const cell = document.createElement('td');
                    const value = resultRow[column];
                    if (value === null) {
                        cell.className = 'biforglpi-null';
                        cell.textContent = 'NULL';
                    } else {
                        cell.textContent = String(value);
                        cell.title = String(value);
                    }
                    row.appendChild(cell);
                });
                body.appendChild(row);
            });
        }

        table.appendChild(body);
        tableWrap.appendChild(table);
    }
})();
