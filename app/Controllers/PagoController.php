<?php
require_once '../app/Models/PaymentModel.php';

class PagoController
{
    private $basePath;
    private $pagoModel;

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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_POST['user_id'] ?? null;
            $bancoSeleccionado = $_POST['banco_seleccionado'] ?? 'Desconocido';
            $file = $_FILES['comprobante'] ?? null;

            if (!$userId || !$file || $file['error'] !== UPLOAD_ERR_OK) {
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/superarseconectadosv2/public/estudiante/index?error=no_file_or_user'));
                exit();
            }

            $uploadDir = '../public/uploads/comprobantes/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = 'comprobante_user_' . $userId . '_' . time() . '.' . $fileExtension;
            $targetFilePath = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {

                $senderEmail    = getenv('SMTP_USER');
                $senderPassword = getenv('SMTP_PASS');
                $recipientEmail = getenv('CORREO_MATRICULAS');

                require '../vendor/autoload.php';

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = getenv('SMTP_HOST');
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $senderEmail;
                    $mail->Password   = $senderPassword;
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom($senderEmail, 'Sistema de Estudiantes Superarse');
                    $mail->addAddress($recipientEmail, 'Departamento de Matrículas');
                    $mail->isHTML(true);
                    $mail->Subject = "✅ Nuevo Comprobante de Pago de Estudiante: " . htmlspecialchars($userId);

                    $message = "
            <html>
            <head><title>Comprobante de Pago Recibido</title></head>
            <body>
                <p>Estimado Departamento de Matrículas,</p>
                <p>Se ha recibido un nuevo comprobante de pago de un estudiante.</p>
                <p><strong>ID de Estudiante:</strong> " . htmlspecialchars($userId) . "</p>
                <p><strong>Banco Seleccionado:</strong> " . htmlspecialchars($bancoSeleccionado) . "</p>
                <p>El archivo se adjunta a este correo para su validación.</p>
                <p style='font-size: 10px; color: #777;'>Ruta de archivo en el servidor: /uploads/comprobantes/" . htmlspecialchars($newFileName) . "</p>
            </body>
            </html>";

                    $mail->Body = $message;
                    $mail->addAttachment($targetFilePath, $newFileName);
                    $mail->send();
                    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/superarseconectadosv2/public/estudiante/index?success=comprobante_enviado'));
                    exit();
                } catch (Exception $e) {
                    error_log("Error al enviar correo con comprobante: {$mail->ErrorInfo}");
                    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/superarseconectadosv2/public/estudiante/index?error=mail_failed'));
                    exit();
                }
            } else {
                error_log("Error al mover el archivo subido: " . $file['tmp_name']);
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/superarseconectadosv2/public/estudiante/index?error=upload_failed'));
                exit();
            }
        }
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/superarseconectadosv2/public/estudiante/index'));
        exit();
    }

    public function procesarPago()
    {
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
}