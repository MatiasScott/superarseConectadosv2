<?php
// app/Models/PagoModel.php
require_once __DIR__ . '/Database.php';

class PagoModel extends Database
{
    /**
     * Obtiene la información de pagos de un estudiante por su número de cédula.
     * Los nombres de las claves del array de retorno coinciden con las columnas actualizadas de la DB.
     *
     * @param string $identificacion La cédula del estudiante.
     * @return array La información de pago o valores predeterminados/error.
     */
    public function getPagosByIdentificacion(string $identificacion)
    {
        $db = $this->getConnection();
        // La consulta trae todas las columnas, por lo que las nuevas y renombradas están disponibles
        $query = "SELECT * FROM pagos_estudiantes WHERE cedula = :identificacion LIMIT 1";

        $stmt = $db->prepare($query);
        try {
            $stmt->execute([':identificacion' => $identificacion]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si no se encuentra ningún resultado
            if (!$result) {
                return [
                    'cuotas' => [],
                    'ABONO_TOTAL2' => null,
                    'SALDO_TOTAL_FINAL' => null,
                    'SALDO_PENDIENTE_PERIODO_ANTERIOR' => null, // Nuevo campo
                    'VALOR_CUOTA_MENSUAL' => null,             // Nuevo campo
                    'observacion' => null,
                ];
            }

            // Retorna los datos mapeados a las nuevas columnas de la DB
            return [
                'cuotas' => [], // Campo placeholder para cuotas individuales si las manejaras aparte
                'ABONO_TOTAL2' => $result['ABONO_TOTAL2'] ?? null,
                'SALDO_TOTAL_FINAL' => $result['SALDO_TOTAL_FINAL'] ?? null,
                'SALDO_PENDIENTE_PERIODO_ANTERIOR' => $result['SALDO_PENDIENTE_PERIODO_ANTERIOR'] ?? null,
                'VALOR_CUOTA_MENSUAL' => $result['VALOR_CUOTA_MENSUAL'] ?? null,
                'observacion' => $result['observacion'] ?? null,
            ];
        } catch (PDOException $e) {
            error_log("Error de SQL en PagoModel: " . $e->getMessage());
            // Retorna valores de error, usando las nuevas claves del array
            return [
                'cuotas' => [],
                'ABONO_TOTAL2' => 'Error de BD',
                'SALDO_TOTAL_FINAL' => 'Error de BD',
                'SALDO_PENDIENTE_PERIODO_ANTERIOR' => 'Error de BD',
                'VALOR_CUOTA_MENSUAL' => 'Error de BD',
                'observacion' => 'Error de BD',
            ];
        }
    }
}
