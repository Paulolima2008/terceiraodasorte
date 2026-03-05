document.addEventListener('DOMContentLoaded', () => {
    const shell = document.getElementById('adminShell');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const expandToggles = document.querySelectorAll('[data-sidebar-expand-toggle]');
    const campaignSwitcher = document.querySelector('[data-campaign-switcher]');
    const autoOpenModalElements = document.querySelectorAll('.modal[data-open-on-load="1"]');
    const sidebarCollapsedStorageKey = 'admin_sidebar_collapsed';

    const isDesktopViewport = () => window.matchMedia('(min-width: 1200px)').matches;

    const syncSidebarCollapseByViewport = () => {
        if (!shell) {
            return;
        }

        if (!isDesktopViewport()) {
            shell.classList.remove('is-sidebar-collapsed');
            return;
        }

        const shouldCollapse = window.localStorage.getItem(sidebarCollapsedStorageKey) === '1';
        shell.classList.toggle('is-sidebar-collapsed', shouldCollapse);
    };

    syncSidebarCollapseByViewport();
    window.addEventListener('resize', syncSidebarCollapseByViewport);

    if (toggle && shell) {
        toggle.addEventListener('click', () => {
            shell.classList.toggle('is-sidebar-open');
        });
    }

    if (expandToggles.length > 0 && shell) {
        expandToggles.forEach((expandToggle) => {
            expandToggle.addEventListener('click', () => {
                if (!isDesktopViewport()) {
                    return;
                }

                shell.classList.toggle('is-sidebar-collapsed');
                const isCollapsed = shell.classList.contains('is-sidebar-collapsed');
                window.localStorage.setItem(sidebarCollapsedStorageKey, isCollapsed ? '1' : '0');
            });
        });
    }

    if (window.bootstrap && autoOpenModalElements.length > 0) {
        autoOpenModalElements.forEach((modalElement) => {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
        });
    }

    if (campaignSwitcher) {
        campaignSwitcher.addEventListener('change', () => {
            const selectedCampaignId = String(campaignSwitcher.value || '').trim();
            if (selectedCampaignId === '') {
                return;
            }

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('campaign_id', selectedCampaignId);
            window.location.href = currentUrl.toString();
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

    const barChartElement = document.getElementById('dashboardChart');
    const pieChartElement = document.getElementById('dashboardPrizeChart');
    if ((barChartElement || pieChartElement) && window.Chart) {
        const metricsSourceElement = barChartElement || pieChartElement;
        const metricsUrl = new URL(metricsSourceElement.dataset.metricsUrl, window.location.origin);
        const activeCampaignId = new URL(window.location.href).searchParams.get('campaign_id');
        if (activeCampaignId) {
            metricsUrl.searchParams.set('campaign_id', activeCampaignId);
        }

        fetch(metricsUrl.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((data) => {
                if (barChartElement) {
                    const vouchersByCampaign = Array.isArray(data.vouchers_by_campaign) ? data.vouchers_by_campaign : [];
                    const labels = vouchersByCampaign.map((item) => item.campaign_name);
                    const soldValues = vouchersByCampaign.map((item) => Number(item.sold_count));
                    const unsoldValues = vouchersByCampaign.map((item) => Number(item.unsold_count));

                    new Chart(barChartElement, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: 'Vendidos',
                                    data: soldValues,
                                    backgroundColor: 'rgba(20, 74, 154, 0.75)',
                                    borderColor: '#144a9a',
                                    borderWidth: 1,
                                },
                                {
                                    label: 'Nao vendidos',
                                    data: unsoldValues,
                                    backgroundColor: 'rgba(83, 133, 217, 0.75)',
                                    borderColor: '#5385d9',
                                    borderWidth: 1,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: true } },
                            scales: {
                                x: {
                                    ticks: {
                                        autoSkip: true,
                                        maxTicksLimit: 12,
                                    },
                                },
                                y: { beginAtZero: true },
                            },
                        },
                    });
                }

                if (pieChartElement) {
                    const prizeChart = data.prize_value_chart || {};
                    const paidValue = Number(prizeChart.paid_value || 0);
                    const unpaidValue = Number(prizeChart.unpaid_value || 0);
                    const brlFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

                    new Chart(pieChartElement, {
                        type: 'pie',
                        data: {
                            labels: ['Valor pago', 'Saldo de premios'],
                            datasets: [
                                {
                                    data: [paidValue, unpaidValue],
                                    backgroundColor: ['#144a9a', '#f3b43f'],
                                    borderColor: ['#0f3876', '#c98f2b'],
                                    borderWidth: 1,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom' },
                                tooltip: {
                                    callbacks: {
                                        label: (context) => `${context.label}: ${brlFormatter.format(Number(context.raw || 0))}`,
                                    },
                                },
                            },
                        },
                    });
                }
            })
            .catch(() => {
                // Dashboard keeps rendering even if the metrics API is temporarily unavailable.
            });
    }
});
