<?php
$status = $_GET['status'] ?? null;

if ($status === 'success') {
    exit();
} elseif ($status === 'failure') {
    exit();
}

if (!isset($GLOBALS['esPasarelaPayphone'])) {
    echo "Acceso directo a la pasarela no permitido. Inicia el pago desde el dashboard.";
    exit();
}
$clientTransactionId = $GLOBALS['clientTransactionId'];
$amount = $GLOBALS['amount'];
$amountWithoutTax = $GLOBALS['amountWithoutTax'];
$tax = $GLOBALS['tax'];
$referencia = $GLOBALS['referencia'];
?>
<main class="container mx-auto p-4 flex-grow flex items-center justify-center">
    <div class="w-full max-w-lg">
        <h1 class="text-3xl font-bold text-center text-superarse-morado-oscuro mb-6">
            Procesando Pago
        </h1>
        <div id="pp-button" class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
            <p class="text-center text-gray-500">El botón de Payphone debe aparecer aquí.</p>
        </div>
    </div>
</main>

<script type="module">
    window.addEventListener('DOMContentLoaded', () => {
        const ppb = new PPaymentButtonBox({
            token: '8W-4m1qWExjBDCoReWHZUSh4B1tNHuK8EGOviJbt4gI6j4pZ_HOxNVRYjevU9CJ-huw21fTmZz0qDOiA_NmzaA0bsVbcYWArG3SkIR3FLnC3qqE_REmuiKy9DefawP-No8nZ-EguZiWBSQHR7CDLiBNgacy7u45Ht2XsO1THDbo6lJS2VnpfmfS1VdCCALbTY7Z8iFFpXJp6IFGFC8NawUZIcVlrMAKSjHc1NF_e1wxgvZ4K8Jg1LKX6MSzsRJ9yloDEB1rWBroX2Lsze61au-D1L_e0-fV6XTwiUKi6vJRoEmNs7soTqEYrBjb6FM9hbmEEpxAzinOjkodgMQWkdT8lSuw',
            clientTransactionId: '<?= $clientTransactionId ?>',
            amount: <?= $amount ?>,
            amountWithoutTax: <?= $amountWithoutTax ?>,
            tax: <?= $tax ?>,
            currency: "USD",
            storeId: "d3fcb722-dfe9-4e7c-8b33-cf8fbd309006",
            reference: '<?= htmlspecialchars($referencia) ?>',
            successUrl: '<?= $basePath ?>/pago?status=success',
            failureUrl: '<?= $basePath ?>/pago?status=failure'
        }).render('pp-button');
    });
</script>