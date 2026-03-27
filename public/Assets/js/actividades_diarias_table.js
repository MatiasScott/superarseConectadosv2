document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('actividades-diarias-container');
  if (!container) return;

  const tableId = 'actividades-diarias-table';
  container.innerHTML = `
    <div class="overflow-x-auto">
      <div class="mb-3 flex justify-between items-center">
        <input id="actividades-search" type="search" placeholder="Buscar actividad..." class="px-3 py-2 border rounded-lg w-1/3" />
        <div>
          <button id="reload-actividades" class="px-3 py-2 bg-superarse-morado-medio text-white rounded-lg">Recargar</button>
        </div>
      </div>
      <table id="${tableId}" class="min-w-full divide-y divide-gray-200 rounded-lg overflow-hidden">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actividad</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Horas</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Observaciones</th>
          </tr>
        </thead>
        <tbody class="bg-white" id="actividades-tbody">
          <tr><td colspan="4" class="text-center py-6 text-gray-500">Cargando...</td></tr>
        </tbody>
      </table>
      <div class="mt-3" id="actividades-pagination"></div>
    </div>
  `;

  const tbody = document.getElementById('actividades-tbody');
  const searchInput = document.getElementById('actividades-search');
  const reloadBtn = document.getElementById('reload-actividades');

  let actividades = [];
  let currentPage = 1;
  const pageSize = 10;

  function formatDate(dateStr) {
    // Expecting YYYY-MM-DD or similar
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString();
  }

  function renderTable() {
    const term = searchInput.value.trim().toLowerCase();
    const filtered = actividades.filter(a => {
      return (
        (a.actividad_realizada || '').toLowerCase().includes(term) ||
        (a.observaciones || '').toLowerCase().includes(term) ||
        (a.fecha_actividad || '').toLowerCase().includes(term)
      );
    });

    const total = filtered.length;
    const pages = Math.max(1, Math.ceil(total / pageSize));
    if (currentPage > pages) currentPage = pages;
    const start = (currentPage - 1) * pageSize;
    const pageItems = filtered.slice(start, start + pageSize);

    tbody.innerHTML = '';
    if (pageItems.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-gray-500">No hay actividades</td></tr>';
    } else {
      for (const a of pageItems) {
        const tr = document.createElement('tr');
        tr.className = 'border-b';
        tr.innerHTML = `
          <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${formatDate(a.fecha_actividad)}</td>
          <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(a.actividad_realizada)}</td>
          <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-superarse-rosa">${escapeHtml(String(a.horas_invertidas))}</td>
          <td class="px-4 py-3 text-sm text-gray-500">${escapeHtml(a.observaciones || '')}</td>
        `;
        tbody.appendChild(tr);
      }
    }

    renderPagination(pages);
  }

  function renderPagination(pages) {
    const pag = document.getElementById('actividades-pagination');
    pag.innerHTML = '';
    if (pages <= 1) return;

    for (let p = 1; p <= pages; p++) {
      const btn = document.createElement('button');
      btn.textContent = p;
      btn.className = `px-3 py-1 mx-1 rounded ${p===currentPage ? 'bg-superarse-morado-medio text-white' : 'bg-white border'}`;
      btn.addEventListener('click', () => {
        currentPage = p;
        renderTable();
      });
      pag.appendChild(btn);
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  async function loadActividades() {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-gray-500">Cargando...</td></tr>';
    try {
      const resp = await fetch('/superarseconectadosv2/public/pasantias/actividadesDiariasJson', { method: 'GET' });
      const data = await resp.json();
      if (data.success) {
        actividades = data.actividades || [];
        currentPage = 1;
        renderTable();
      } else {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-6 text-red-600">${escapeHtml(data.message || 'Error al cargar')}</td></tr>`;
      }
    } catch (err) {
      console.error(err);
      tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-red-600">Error de red al cargar actividades</td></tr>';
    }
  }

  searchInput.addEventListener('input', () => {
    currentPage = 1;
    renderTable();
  });

  reloadBtn.addEventListener('click', () => loadActividades());

  // Inicializa
  loadActividades();
});