(function () {
  const payload = window.ADMIN_DASHBOARD_DATA || {};

  function mapChartData(rows) {
    return {
      labels: rows.map((r) => r.etiqueta || r.periodo || 'N/D'),
      values: rows.map((r) => Number(r.total || 0)),
    };
  }

  function createChart(elId, config) {
    const el = document.getElementById(elId);
    if (!el || typeof Chart === 'undefined') {
      return;
    }
    // eslint-disable-next-line no-new
    new Chart(el, config);
  }

  const porMes = mapChartData(payload.porMes || []);
  createChart('chartPorMes', {
    type: 'line',
    data: {
      labels: porMes.labels,
      datasets: [
        {
          label: 'Practicas registradas',
          data: porMes.values,
          borderColor: '#7c3aed',
          backgroundColor: 'rgba(124,58,237,0.16)',
          borderWidth: 2,
          fill: true,
          tension: 0.35,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: { y: { beginAtZero: true } },
    },
  });

  const topEmpresas = mapChartData(payload.topEmpresas || []);
  createChart('chartEmpresas', {
    type: 'bar',
    data: {
      labels: topEmpresas.labels,
      datasets: [
        {
          label: 'Practicas',
          data: topEmpresas.values,
          backgroundColor: '#6366f1',
        },
      ],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true } },
    },
  });

  const porCarrera = mapChartData(payload.porCarrera || []);
  createChart('chartCarrera', {
    type: 'doughnut',
    data: {
      labels: porCarrera.labels,
      datasets: [
        {
          data: porCarrera.values,
          backgroundColor: [
            '#7c3aed',
            '#ec4899',
            '#0ea5e9',
            '#22c55e',
            '#f59e0b',
            '#ef4444',
            '#14b8a6',
            '#6366f1',
          ],
        },
      ],
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom' } },
    },
  });
})();
