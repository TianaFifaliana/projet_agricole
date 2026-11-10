import Chart from 'chart.js/auto';

const source = document.getElementById('dashboard-charts-data');

if (source !== null) {
    const data = JSON.parse(source.textContent);

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } },
    };

    if (document.getElementById('chart-quota-eau') !== null) {
        new Chart(document.getElementById('chart-quota-eau'), {
            type: 'bar',
            data: {
                labels: data.quotaEau.labels,
                datasets: [
                    {
                        label: 'Alloué (m³)',
                        data: data.quotaEau.alloue,
                        backgroundColor: '#38bdf8',
                    },
                    {
                        label: 'Consommé (m³)',
                        data: data.quotaEau.consomme,
                        backgroundColor: '#10b981',
                    },
                ],
            },
            options,
        });
    }

    if (document.getElementById('chart-finances') !== null) {
        new Chart(document.getElementById('chart-finances'), {
            type: 'bar',
            data: {
                labels: data.finances.labels,
                datasets: [
                    {
                        label: 'Recettes (€ HT)',
                        data: data.finances.recettes,
                        backgroundColor: '#10b981',
                    },
                    {
                        label: 'Dépenses (€)',
                        data: data.finances.depenses,
                        backgroundColor: '#f43f5e',
                    },
                ],
            },
            options,
        });
    }

    if (document.getElementById('chart-rendement') !== null) {
        new Chart(document.getElementById('chart-rendement'), {
            type: 'bar',
            data: {
                labels: data.rendement.labels,
                datasets: [
                    {
                        label: 'Rendement (t/ha)',
                        data: data.rendement.rendements,
                        backgroundColor: '#f59e0b',
                    },
                ],
            },
            options,
        });
    }
}
