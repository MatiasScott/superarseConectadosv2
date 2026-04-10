<?php
$infoPagos = (isset($data['infoPagos']) && is_array($data['infoPagos'])) ? $data['infoPagos'] : [];
$bancos = (isset($data['bancos']) && is_array($data['bancos'])) ? $data['bancos'] : [];
$basePath = $data['basePath'] ?? '';
$uploadFeedback = $_GET['success'] ?? $_GET['error'] ?? null;
$uploadFeedbackType = isset($_GET['success']) ? 'success' : (isset($_GET['error']) ? 'error' : null);

$uploadFeedbackMessages = [
    'comprobante_enviado' => 'Comprobante enviado correctamente para revisión.',
    'not_authenticated' => 'Tu sesión expiró. Inicia sesión nuevamente para enviar el comprobante.',
    'invalid_request' => 'La solicitud no pasó la validación de seguridad. Intenta nuevamente.',
    'missing_bank' => 'Selecciona un banco antes de enviar el comprobante.',
    'no_file_or_user' => 'Adjunta un comprobante válido antes de continuar.',
    'invalid_file_type' => 'Solo se permiten archivos PDF, JPG o PNG.',
    'file_too_large' => 'El comprobante excede el tamaño máximo permitido de 5 MB.',
    'upload_failed' => 'No fue posible guardar el comprobante. Intenta nuevamente.',
    'mail_failed' => 'El comprobante se guardó, pero no fue posible enviar la notificación por correo.',
    'storage_failed' => 'No fue posible preparar el almacenamiento del comprobante.',
];

$formatMontoResumen = static function ($valor): string {
    if ($valor === null || $valor === '' || !is_numeric($valor)) {
        return '0.00';
    }

    return number_format((float) $valor, 2, '.', '');
};
?>

<div id="pagos" class="tab-pane hidden">
    <?php if ($uploadFeedback !== null && isset($uploadFeedbackMessages[$uploadFeedback])): ?>
        <div class="mb-4 rounded-lg border px-4 py-3 text-sm <?php echo $uploadFeedbackType === 'success'
            ? 'border-green-200 bg-green-50 text-green-800'
            : 'border-red-200 bg-red-50 text-red-800'; ?>">
            <?php echo htmlspecialchars($uploadFeedbackMessages[$uploadFeedback], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <h3 class="text-xl font-semibold text-superarse-morado-oscuro mb-4">Resumen Financiero</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">

        <!-- Tarjeta 1: ABONO TOTAL (EXISTENTE) -->
        <div class="bg-green-100 p-4 rounded-lg shadow-md border border-green-300">
            <p class="text-sm font-medium text-green-700">ABONO TOTAL</p>
            <p class="text-2xl font-bold text-green-800 mt-1">
                $<?php echo $formatMontoResumen($infoPagos['ABONO_TOTAL2'] ?? 0); // Usando ABONO_TOTAL2, el nombre estandarizado en DB 
                    ?>
            </p>
        </div>

        <!-- Tarjeta 2: SALDO TOTAL PENDIENTE (EXISTENTE) -->
        <div class="bg-red-100 p-4 rounded-lg shadow-md border border-red-300">
            <p class="text-sm font-medium text-red-700">SALDO TOTAL PENDIENTE</p>
            <p class="text-2xl font-bold text-red-800 mt-1">
                $<?php echo $formatMontoResumen($infoPagos['SALDO_TOTAL_FINAL'] ?? 0); // Usando SALDO_TOTAL_FINAL, el nombre estandarizado en DB 
                    ?>
            </p>
        </div>

        <!-- Tarjeta 3: SALDO PENDIENTE PERIODO ANTERIOR (NUEVA) -->
        <div class="bg-yellow-100 p-4 rounded-lg shadow-md border border-yellow-300">
            <p class="text-sm font-medium text-yellow-700">SALDO PERIODO ANTERIOR</p>
            <p class="text-2xl font-bold text-yellow-800 mt-1">
                $<?php echo $formatMontoResumen($infoPagos['SALDO_PENDIENTE_PERIODO_ANTERIOR'] ?? 0); // NUEVO CAMPO: alineado a DB 
                    ?>
            </p>
        </div>

        <!-- Tarjeta 4: VALOR CUOTA MENSUAL (NUEVA) -->
        <div class="bg-indigo-100 p-4 rounded-lg shadow-md border border-indigo-300">
            <p class="text-sm font-medium text-indigo-700">VALOR CUOTA MENSUAL</p>
            <p class="text-2xl font-bold text-indigo-800 mt-1">
                $<?php echo $formatMontoResumen($infoPagos['VALOR_CUOTA_MENSUAL'] ?? 0); // NUEVO CAMPO: alineado a DB 
                    ?>
            </p>
        </div>

        <!-- Tarjeta 5: ESTADO / OBSERVACIÓN (EXISTENTE) -->
        <!-- Esta tarjeta ocupa el quinto espacio en el grid de 5 columnas -->
        <div class="bg-blue-100 p-4 rounded-lg shadow-md border border-blue-300">
            <p class="text-sm font-medium text-blue-700">OBSERVACIÓN</p>
            <p class="text-base text-blue-800 mt-2 overflow-auto max-h-20 text-ellipsis">
                <?php echo nl2br(htmlspecialchars($infoPagos['observacion'] ?? 'Sin observaciones de pago.')); // Se mantiene la observación 
                ?>
            </p>
        </div>
    </div>
    <hr class="my-8 border-gray-300">
    <h3 class="text-xl font-semibold text-superarse-morado-oscuro mb-4">Pago por Transferencia
        Bancaria
    </h3>
    <p class="text-gray-700 mb-6">Selecciona una de las siguientes cuentas para realizar tu
        transferencia:</p>

    <div class="flex flex-wrap gap-3 mb-6" id="bank-buttons">
        <?php if (!empty($bancos)): // USANDO $data 
        ?>
            <?php foreach ($bancos as $index => $banco): // USANDO $data 
            ?>
                <button type="button"
                    class="bank-tab-button w-full sm:w-auto px-4 sm:px-6 py-3 rounded-lg text-white font-semibold transition duration-300 
                                    <?php echo ($index === 0) ? 'bg-superarse-morado-oscuro hover:bg-superarse-morado-medio' : 'bg-gray-400 hover:bg-gray-500'; ?>"
                    data-bank-id="<?php echo htmlspecialchars($banco['id']); ?>"
                    data-bank-name="<?php echo htmlspecialchars($banco['nombre_banco']); ?>"
                    data-account-type="<?php echo htmlspecialchars($banco['tipo_cuenta']); ?>"
                    data-account-number="<?php echo htmlspecialchars($banco['numero_cuenta']); ?>">
                    <?php echo htmlspecialchars($banco['nombre_banco']); ?>
                </button>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-500">No hay información de bancos disponible.</p>
        <?php endif; ?>
    </div>

    <div id="bank-details" class="bg-gray-50 p-6 rounded-lg shadow-md border border-gray-200 mb-8">
        <p class="mb-2"><strong class="text-superarse-morado-oscuro">Banco:</strong> <span
                id="selected-bank-name"></span></p>
        <p class="mb-2"><strong class="text-superarse-morado-oscuro">Tipo de cuenta:</strong> <span
                id="selected-account-type"></span></p>
        <p class="mb-2"><strong class="text-superarse-morado-oscuro">Número de Cuenta:</strong>
            <span id="selected-account-number" class="break-all"></span>
        </p>
        <p class="mb-2"><strong class="text-superarse-morado-oscuro">RUC:</strong> 1792951704001</p>
        <p class="mb-4"><strong class="text-superarse-morado-oscuro">Nombre del
                Beneficiario:</strong>
            Instituto Superior Tecnológico Superarse</p>
    </div>

    <h4 class="text-lg font-semibold text-superarse-morado-oscuro mb-3">
        Paso 3: Envía tu Notificación de Pago
    </h4>

    <div class="mt-4 p-3 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded-lg mb-6">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Importante: adjunta tu comprobante en PDF, JPG o PNG. El archivo se almacenará de forma segura y se notificará automáticamente al área de matrículas.
    </div>

    <form id="upload-form" method="POST" action="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>/pagos/upload-comprobante" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrfTokenPagoUpload'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="banco_seleccionado" id="banco_seleccionado" value="">

        <div>
            <label for="comprobante" class="block text-sm font-medium text-gray-700 mb-2">Adjuntar comprobante</label>
            <input type="file" id="comprobante" name="comprobante" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                class="block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-superarse-morado-oscuro focus:border-superarse-morado-oscuro bg-white"
                required>
            <p class="mt-2 text-sm text-gray-500">Máximo 5 MB. Formatos permitidos: PDF, JPG y PNG.</p>
        </div>

        <button type="submit" id="send-comprobante-btn" disabled
            class="inline-flex w-full sm:w-auto justify-center bg-superarse-rosa text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-300 opacity-50 cursor-not-allowed">
            Enviar Comprobante
        </button>
    </form>
    <hr class="my-8 border-gray-300">
    <h3 class="text-xl font-semibold text-superarse-morado-oscuro mb-4 mt-6">Pago a través de
        Payphone
    </h3>
    <div class="bg-gray-50 p-6 rounded-lg shadow-md border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-money-bill-wave mr-1 text-superarse-rosa"></i> Cantidad a Pagar
                    (USD)
                </label>
                <input type="text" id="cantidad"
                    class="block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-superarse-morado-oscuro focus:border-superarse-morado-oscuro"
                    placeholder="Ej: 50.00 o 12.50" inputmode="decimal">
            </div>

            <div>
                <label for="referencia" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-tag mr-1 text-superarse-rosa"></i> Referencia / Descripción
                </label>
                <input type="text" id="referencia"
                    class="block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-superarse-morado-oscuro focus:border-superarse-morado-oscuro"
                    placeholder="Ej: Pago Matrícula, Cuota de Marzo, etc.">
            </div>
        </div>

        <p class="mt-6 text-sm text-gray-600">
            Al hacer clic, serás redirigido a la pasarela de Payphone para completar el pago.
        </p>

        <button id="payphone-link"
            class="mt-4 w-full sm:w-auto py-3 px-8 bg-superarse-rosa text-white font-bold rounded-lg shadow-lg transition duration-300 hover:bg-pink-700 focus:outline-none focus:ring-4 focus:ring-pink-300"
            target="_blank">
            <i class="fas fa-credit-card mr-2"></i> Pagar con Payphone
        </button>
    </div>
</div>