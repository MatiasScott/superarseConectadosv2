<?php
require_once '../app/Models/PaymentModel.php';
require_once '../app/Helpers/AuthSecurity.php';

class PagoController
{
    private $basePath;
    private $pagoModel;
    private $maxUploadSize = 5242880;

    public function __construct()
    {
        // Configurar basePath según el entorno
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'superarse.ec') !== false) {
            $this->basePath = '';
        } else {
            $this->basePath = '/superarseconectadosv2/public';
        }
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['authenticated']) && !empty($_SESSION['must_change_password'])) {
            header("Location: " . $this->basePath . "/password/change");
            exit();
        }

        $this->pagoModel = new PaymentModel();
    }

    public function uploadComprobante()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPagos();
        }

        if (!$this->isAuthenticatedStudent()) {
            header('Location: ' . $this->basePath . '/login?error=not_authenticated');
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('student_payment_upload', $_POST['csrf_token'] ?? '')) {
            $this->redirectToPagos('error=invalid_request');
        }

        $userId = (int) ($_SESSION['id_usuario'] ?? 0);
        $studentIdentification = trim((string) ($_SESSION['identificacion'] ?? ''));
        $studentName = trim((string) ($_SESSION['nombres_completos'] ?? 'Estudiante'));
        $bancoSeleccionado = trim((string) ($_POST['banco_seleccionado'] ?? ''));
        $file = $_FILES['comprobante'] ?? null;

        if ($userId <= 0 || !$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->redirectToPagos('error=no_file_or_user');
        }

        if ($bancoSeleccionado === '') {
            $this->redirectToPagos('error=missing_bank');
        }

        try {
            [$safeExtension, $safeMime] = $this->validateUploadedReceipt($file);
            $uploadDir = $this->getUploadStorageDirectory();

            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true) && !is_dir($uploadDir)) {
                throw new RuntimeException('No fue posible crear el directorio de almacenamiento.');
            }

            $newFileName = 'comprobante_user_' . $userId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $safeExtension;
            $targetFilePath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

            if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                error_log('Error al mover el archivo subido: ' . ($file['tmp_name'] ?? 'sin_tmp_name'));
                $this->redirectToPagos('error=upload_failed');
            }

            @chmod($targetFilePath, 0640);

            $senderEmail = trim((string) getenv('SMTP_USER'));
            $senderPassword = trim((string) getenv('SMTP_PASS'));
            $smtpHost = trim((string) getenv('SMTP_HOST'));
            $recipientEmail = trim((string) getenv('CORREO_MATRICULAS'));

            if ($senderEmail === '' || $senderPassword === '' || $smtpHost === '' || $recipientEmail === '') {
                error_log('Variables SMTP incompletas para notificación de comprobantes.');
                $this->redirectToPagos('error=mail_failed');
            }

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $senderEmail;
                $mail->Password = $senderPassword;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom($senderEmail, 'Sistema de Estudiantes Superarse');
                $mail->addAddress($recipientEmail, 'Departamento de Matrículas');
                $mail->isHTML(true);
                $mail->Subject = 'Nuevo comprobante de pago - ' . $studentIdentification;

                $message = '
                    <html>
                    <head><title>Comprobante de Pago Recibido</title></head>
                    <body>
                        <p>Estimado Departamento de Matrículas,</p>
                        <p>Se ha recibido un nuevo comprobante de pago para revisión.</p>
                        <p><strong>ID de Estudiante:</strong> ' . htmlspecialchars((string) $userId, ENT_QUOTES, 'UTF-8') . '</p>
                        <p><strong>Cédula:</strong> ' . htmlspecialchars($studentIdentification, ENT_QUOTES, 'UTF-8') . '</p>
                        <p><strong>Nombre:</strong> ' . htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') . '</p>
                        <p><strong>Banco Seleccionado:</strong> ' . htmlspecialchars($bancoSeleccionado, ENT_QUOTES, 'UTF-8') . '</p>
                        <p><strong>Tipo de archivo:</strong> ' . htmlspecialchars($safeMime, ENT_QUOTES, 'UTF-8') . '</p>
                        <p>El comprobante se adjunta a este correo para su validación.</p>
                    </body>
                    </html>';

                $mail->Body = $message;
                $mail->addAttachment($targetFilePath, $newFileName);
                $mail->send();
                $this->redirectToPagos('success=comprobante_enviado');
            } catch (Exception $e) {
                error_log('Error al enviar correo con comprobante: ' . $mail->ErrorInfo);
                $this->redirectToPagos('error=mail_failed');
            }
        } catch (RuntimeException $exception) {
            error_log('Error de validación/almacenamiento del comprobante: ' . $exception->getMessage());
            $errorCode = $exception->getCode();

            if ($errorCode === 1001) {
                $this->redirectToPagos('error=file_too_large');
            }

            if ($errorCode === 1002) {
                $this->redirectToPagos('error=invalid_file_type');
            }

            $this->redirectToPagos('error=storage_failed');
        }
    }

    public function procesarPago()
    {
        if (!$this->isAuthenticatedStudent()) {
            header('Location: ' . $this->basePath . '/login?error=not_authenticated');
            exit();
        }

        $userId = $_SESSION['id_usuario'] ?? null;
        $status = $_GET['status'] ?? null;
        $vista = $_GET['vista'] ?? null;
        if ($status === 'success' || $status === 'failure') {
            require_once '../Views/dashboard/tab_pagos.php';
            return;
        } elseif ($vista === 'pasarela' && isset($_GET['cantidad']) && $userId) {

            $cantidad_url = $_GET['cantidad'] ?? 0;
            $cantidad_base = max(0.0, floatval($cantidad_url));
            $referencia_raw = $_GET['referencia'] ?? "Pago de Superarse";

            list($usec, $sec) = explode(" ", microtime());
            $milisegundos = round($usec * 1000);
            $tiempo_actual = date("H_i_s", $sec) . '_' . sprintf('%03d', $milisegundos);
            $clientTransactionId = "Superarse_" . $tiempo_actual;

            $GLOBALS['clientTransactionId'] = $clientTransactionId;
            $GLOBALS['amount'] = intval(round($cantidad_base * 100));
            $GLOBALS['amountWithoutTax'] = $GLOBALS['amount'];
            $GLOBALS['tax'] = 0.0;
            $GLOBALS['referencia'] = htmlspecialchars($referencia_raw);
            $GLOBALS['esPasarelaPayphone'] = true;

            $basePath = $this->basePath;
            $title = 'Pasarela de pagos - Superarse';
            $headerTitle = 'Plataforma de Pagos - Superarse';
            $bodyClass = 'bg-gray-100 min-h-screen flex flex-col';
            $moduleCss = ['login.css'];
            $moduleHeadStyles = [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
                'https://cdn.payphonetodoesposible.com/box/v1.1/payphone-payment-box.css'
            ];
            $moduleHeadRaw = [
                '<script src="https://cdn.payphonetodoesposible.com/box/v1.1/payphone-payment-box.js" type="module"></script>'
            ];
            $content = __DIR__ . '/../Views/payphone/pasarela_caja.php';

            require __DIR__ . '/../Views/Layouts/auth_layout.php';
            return;
        } else {
            header("Location: /superarseconectadosv2/public/estudiante/pagos");
            exit();
        }
    }

    private function isAuthenticatedStudent()
    {
        return !empty($_SESSION['authenticated'])
            && (($_SESSION['auth_role'] ?? 'student') === 'student')
            && !empty($_SESSION['id_usuario']);
    }

    private function redirectToPagos($query = '')
    {
        $location = $this->basePath . '/estudiante/informacion?module=pagos';
        if ($query !== '') {
            $location .= '&' . ltrim($query, '?&');
        }

        header('Location: ' . $location);
        exit();
    }

    private function validateUploadedReceipt(array $file)
    {
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new RuntimeException('Archivo vacío.', 1002);
        }

        if ($size > $this->maxUploadSize) {
            throw new RuntimeException('Archivo demasiado grande.', 1001);
        }

        $tmpName = $file['tmp_name'] ?? '';
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Archivo temporal inválido.', 1002);
        }

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $originalExtension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($originalExtension, $allowedExtensions, true)) {
            throw new RuntimeException('Extensión no permitida.', 1002);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower((string) $finfo->file($tmpName));
        $allowedMimeMap = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if (!isset($allowedMimeMap[$mime])) {
            throw new RuntimeException('MIME no permitido.', 1002);
        }

        if ($mime === 'application/pdf' && $originalExtension !== 'pdf') {
            throw new RuntimeException('La extensión no coincide con el archivo PDF.', 1002);
        }

        if ($mime === 'image/jpeg' && !in_array($originalExtension, ['jpg', 'jpeg'], true)) {
            throw new RuntimeException('La extensión no coincide con la imagen JPEG.', 1002);
        }

        if ($mime === 'image/png' && $originalExtension !== 'png') {
            throw new RuntimeException('La extensión no coincide con la imagen PNG.', 1002);
        }

        return [$allowedMimeMap[$mime], $mime];
    }

    private function getUploadStorageDirectory()
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'comprobantes';
    }
}