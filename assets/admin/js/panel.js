document.addEventListener('DOMContentLoaded', () => {
    const shell = document.getElementById('adminShell');
    const toggle = document.querySelector('[data-sidebar-toggle]');

    if (toggle && shell) {
        toggle.addEventListener('click', () => {
            shell.classList.toggle('is-sidebar-open');
        });
    }

    if (window.jQuery) {
        $('.js-datatable').DataTable({
            pageLength: 10,
            responsive: true,
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_',
                paginate: {
                    previous: 'Anterior',
                    next: 'Próximo',
                },
            },
        });
    }

    const chartElement = document.getElementById('dashboardChart');
    if (chartElement && window.Chart) {
        fetch(chartElement.dataset.metricsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((data) => {
                const labels = data.monthly.map((item) => item.month_key);
                const values = data.monthly.map((item) => Number(item.total));

                new Chart(chartElement, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Giros',
                            data: values,
                            borderColor: '#c81f25',
                            backgroundColor: 'rgba(200, 31, 37, 0.12)',
                            fill: true,
                            tension: 0.35,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } },
                    },
                });
            })
            .catch(() => {
                // Dashboard keeps rendering even if the metrics API is temporarily unavailable.
            });
    }
});
