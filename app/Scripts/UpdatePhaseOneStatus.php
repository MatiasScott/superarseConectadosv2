<?php
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    require_once __DIR__ . '/../Models/PasantiaModel.php';

    $id_practica = filter_input(INPUT_POST, 'id_practica', FILTER_VALIDATE_INT);

    if ($id_practica) {
        $pasantiaModel = new PasantiaModel();

        $message = "Intentando actualizar la fase 1 para la práctica con ID: " . htmlspecialchars($id_practica) . "<br>";

        $filasAfectadas = $pasantiaModel->marcarFaseUnoComoCompletada($id_practica);

        if ($filasAfectadas === false) {
            $message .= "<strong>¡Error!</strong> Ocurrió un problema al ejecutar la actualización. Revisa el log de errores.";
        } elseif ($filasAfectadas > 0) {
            $message .= "<strong>¡Éxito!</strong> Se ha marcado la fase uno como completada. Filas actualizadas: " . $filasAfectadas;
        } else {
            $message .= "La operación se ejecutó, pero no se encontró ninguna práctica con el ID " . htmlspecialchars($id_practica) . ". Ninguna fila fue actualizada.";
        }
    } else {
        $message = "<strong>Error:</strong> Por favor, ingresa un ID de práctica válido (solo números).";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Estado de Práctica</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f9; color: #333; margin: 0; padding: 2em; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 2em; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #0056b3; }
        form { display: flex; flex-direction: column; gap: 1em; }
        label { font-weight: bold; }
        input[type="number"] { padding: 0.8em; border: 1px solid #ccc; border-radius: 4px; font-size: 1em; }
        button { background-color: #007bff; color: white; padding: 0.8em 1.2em; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        button:hover { background-color: #0056b3; }
        .result { margin-top: 1.5em; padding: 1em; border-radius: 4px; border: 1px solid #ddd; background-color: #e9ecef; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Actualizar Fase 1 de Práctica</h1>
        <p>Ingresa el ID de la práctica para marcar su primera fase como completada.</p>

        <form method="POST" action="">
            <label for="id_practica">ID de la Práctica:</label>
            <input type="number" id="id_practica" name="id_practica" placeholder="Ej: 23" required>
            
            <button type="submit">Actualizar Estado</button>
        </form>

        <?php if (!empty($message)): ?>
            <div class="result">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>