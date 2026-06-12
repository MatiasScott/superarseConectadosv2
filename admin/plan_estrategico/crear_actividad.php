<div class="max-w-5xl mx-auto">
    <div class="bg-white shadow-lg rounded-2xl overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-purple-900 to-purple-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white">Nueva Actividad</h2>
            <a href="<?= $basePath ?>/admin/poa" class="inline-flex items-center gap-1.5 bg-white/20 hover:bg-white/30 text-white text-sm font-medium px-3 py-1.5 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver
            </a>
        </div>
        <div class="p-6">
            <form method="POST" action="<?= $basePath ?>/admin/actividad/store" class="space-y-4">

                <div class="flex items-start gap-2 p-3 rounded-lg bg-blue-50 border border-blue-200 mb-4">
                    <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs text-blue-700 leading-relaxed">Al seleccionar un <strong>Eje Estratégico</strong>, los campos de <strong>Objetivo Estratégico</strong> y <strong>Estrategia</strong> se filtrarán automáticamente para mostrar solo las opciones vinculadas a dicho eje.</p>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Eje Estratégico (PEDI)</label>
                        <select name="eje_id" id="inputEje" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                            <option value="">Seleccione un eje</option>
                            <?php foreach ($ejes as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Objetivo Estratégico (PEDI)</label>
                        <select name="objetivo_id" id="inputObjetivo" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                            <option value="">Seleccione un objetivo</option>
                            <?php foreach ($objetivos as $o): ?>
                            <option value="<?= $o['id'] ?>" data-eje-id="<?= $o['eje_id'] ?>"><?= htmlspecialchars($o['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estrategia (PEDI)</label>
                        <select name="estrategia_id" id="inputEstrategia" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                            <option value="">Seleccione una estrategia</option>
                            <?php foreach ($estrategias as $s): ?>
                            <option value="<?= $s['id'] ?>" data-objetivo-id="<?= $s['objetivo_id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre del Proyecto/Actividad</label>
                    <input type="text" name="nombre_actividad" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Descripción/Insumos para la Actividad</label>
                    <textarea name="observacion_actividad" rows="3" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="Ingrese descripción e insumos..."></textarea>
                </div>

                <div class="grid grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Meta (PEDI)</label>
                        <input type="text" name="meta" id="inputMeta" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm bg-gray-100 focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="Auto-asignada al seleccionar eje" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sede</label>
                        <select name="sede_id" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                            <option value="">Seleccione una sede</option>
                            <?php foreach ($sedes as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Laboratorio</label>
                        <input type="text" name="laboratorio" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="Nombre del laboratorio">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">PROCESOS</label>
                        <select name="area_id" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                            <option value="">Seleccione un área</option>
                            <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex items-start gap-2 p-3 rounded-lg bg-amber-50 border border-amber-200 mb-4">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <p class="text-xs text-amber-700 leading-relaxed">Una vez guardada la actividad, el cronograma no podrá ser modificado. Cualquier cambio debe ser notificado al departamento de <strong>TICS</strong>.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Cronograma</label>
                    <div class="grid grid-cols-6 gap-3">
                        <?php
                        $meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
                        $mesesLabel = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                        foreach ($meses as $i => $m):
                        ?>
                        <label class="mes-checkbox flex flex-col items-center gap-1.5 p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-purple-50 hover:border-purple-300 transition" style="user-select:none;">
                            <input type="checkbox" name="<?= $m ?>_pct" value="100" class="w-4 h-4 text-purple-700 rounded border-gray-300 focus:ring-purple-500" onchange="this.parentElement.classList.toggle('bg-purple-100',this.checked);this.parentElement.classList.toggle('border-purple-500',this.checked)">
                            <span class="text-xs font-medium text-gray-600"><?= $mesesLabel[$i] ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Presupuesto Planificado</label>
                        <input type="number" step="0.01" name="presupuesto_planificado" id="presupuestoPlanificado" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Presupuesto Ejecutado</label>
                        <input type="number" step="0.01" name="presupuesto_ejecutado" id="presupuestoEjecutado" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                    </div>
                    <div id="porcentajeEjecucionContainer" class="col-span-2 hidden">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Avance Ejecutado (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="avance_ejecutado" value="0" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Observación de Avance</label>
                        <input type="text" name="observaciones_avance" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="Ingrese observación del avance ejecutado...">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" id="inputFechaInicio" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha Fin</label>
                        <input type="date" name="fecha_fin" id="inputFechaFin" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                    </div>
                    <div id="inputEstadoBadge" class="col-span-2 hidden">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                    <input type="text" name="observaciones" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="Ingrese observaciones...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="estado" id="inputEstado" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                        <option value="ACTIVO">Activo</option>
                        <option value="INACTIVO">Inactivo</option>
                        <option value="CADUCADO">Caducado</option>
                    </select>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition">Guardar</button>
                    <a href="<?= $basePath ?>/admin/poa" class="bg-gray-400 hover:bg-gray-500 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition">Cancelar</a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    const metasPorEje = <?= json_encode(array_column($metasEje, 'meta_texto', 'eje_id')) ?>;

    document.addEventListener("DOMContentLoaded", function() {
        const inputEje = document.getElementById("inputEje");
        const inputObjetivo = document.getElementById("inputObjetivo");
        const inputEstrategia = document.getElementById("inputEstrategia");
        const inputMeta = document.getElementById("inputMeta");

        function actualizarMeta() {
            const ejeVal = inputEje.value;
            inputMeta.value = metasPorEje[ejeVal] || '';
        }

        function cascadaObjetivos() {
            const ejeSel = inputEje.value;
            for (const opt of inputObjetivo.options) {
                if (!opt.value) continue;
                opt.style.display = (opt.dataset.ejeId === ejeSel || !ejeSel) ? "" : "none";
            }
            if (inputObjetivo.value && [...inputObjetivo.options].some(o => o.value === inputObjetivo.value && o.style.display === "none")) {
                inputObjetivo.value = "";
            }
            cascadaEstrategias();
        }

        function cascadaEstrategias() {
            const objSel = inputObjetivo.value;
            for (const opt of inputEstrategia.options) {
                if (!opt.value) continue;
                opt.style.display = (opt.dataset.objetivoId === objSel || !objSel) ? "" : "none";
            }
            if (inputEstrategia.value && [...inputEstrategia.options].some(o => o.value === inputEstrategia.value && o.style.display === "none")) {
                inputEstrategia.value = "";
            }
        }

        inputEje.addEventListener("change", function() {
            cascadaObjetivos();
            actualizarMeta();
        });
        inputObjetivo.addEventListener("change", cascadaEstrategias);

        // Auto-estado badge
        const fechaInicio = document.getElementById("inputFechaInicio");
        const fechaFin = document.getElementById("inputFechaFin");
        const estadoBadge = document.getElementById("inputEstadoBadge");
        const estadoSelect = document.querySelector("select[name='estado']");

        function actualizarEstadoBadge() {
            const fin = fechaFin.value;
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            if (!fin) { estadoBadge.classList.add("hidden"); return; }
            const fechaFinDt = new Date(fin + "T00:00:00");
            const diffMs = fechaFinDt - hoy;
            const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));
            const badge = estadoBadge.querySelector("span");
            if (diffDays < 0) {
                const daysPast = Math.abs(diffDays);
                badge.className = "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-red-100 text-red-800";
                badge.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Caducado hace ' + daysPast + ' día' + (daysPast !== 1 ? 's' : '');
                estadoBadge.classList.remove("hidden");
                estadoSelect.value = "CADUCADO";
            } else {
                badge.className = "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-green-100 text-green-800";
                badge.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Faltan ' + diffDays + ' día' + (diffDays !== 1 ? 's' : '');
                estadoBadge.classList.remove("hidden");
                if (estadoSelect.value === "CADUCADO") estadoSelect.value = "ACTIVO";
            }
        }
        fechaInicio.addEventListener("change", actualizarEstadoBadge);
        fechaFin.addEventListener("change", actualizarEstadoBadge);

        const pp = document.getElementById("presupuestoPlanificado");
        const pe = document.getElementById("presupuestoEjecutado");
        pp.addEventListener("input", actualizarPorcentajeEjecucion);
        pe.addEventListener("input", actualizarPorcentajeEjecucion);

        cascadaObjetivos();
        actualizarMeta();
        actualizarPorcentajeEjecucion();
    });

    function actualizarPorcentajeEjecucion() {
        const planifInput = document.getElementById("presupuestoPlanificado");
        const ejecInput = document.getElementById("presupuestoEjecutado");
        const planif = parseFloat(planifInput.value) || 0;
        const ejec = parseFloat(ejecInput.value) || 0;
        const container = document.getElementById("porcentajeEjecucionContainer");
        const span = container.querySelector("span");
        if (planif > 0 && ejec > planif) {
            ejecInput.setCustomValidity("El presupuesto ejecutado no puede ser mayor al planificado.");
            ejecInput.reportValidity();
        } else {
            ejecInput.setCustomValidity("");
        }
        if (planif > 0) {
            const pct = Math.max(0, Math.min(100, Math.round(((planif - ejec) / planif) * 100)));
            const color = pct >= 70 ? "bg-green-100 text-green-800" : (pct >= 50 ? "bg-yellow-100 text-yellow-800" : "bg-red-100 text-red-800");
            span.className = "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium " + color;
            span.textContent = "% Ejecución Presupuestaria: " + pct + "%";
            container.classList.remove("hidden");
        } else {
            container.classList.add("hidden");
        }
    }
</script>
