<?php

require_once __DIR__ . '/Database.php';

class ConvenioModel extends Database
{

    private function resolverEstadosPorFechaFin(array $data): array
    {
        $fechaFin = trim((string)($data['fecha_fin'] ?? ''));
        $hoy = date('Y-m-d');

        if ($fechaFin !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin) === 1) {
            if ($fechaFin < $hoy) {
                return [
                    'estado' => 'Inactivo',
                    'estado_convenio' => 'caducado'
                ];
            }

            return [
                'estado' => 'Activo',
                'estado_convenio' => 'vigente'
            ];
        }

        return [
            'estado' => (string)($data['estado'] ?? 'Activo'),
            'estado_convenio' => strtolower((string)($data['estado_convenio'] ?? 'vigente'))
        ];
    }

    public function caducarVencidos()
    {
        $db = $this->getConnection();

        $query = "UPDATE convenios
                  SET estado = 'Inactivo',
                      estado_convenio = 'caducado'
                  WHERE fecha_fin < CURDATE()
                    AND (estado != 'Inactivo' OR estado_convenio != 'caducado')";

        $stmt = $db->prepare($query);
        $stmt->execute();
    }

    public function obtenerConvenios()
    {
        $db = $this->getConnection();

        $query = "SELECT * FROM convenios ORDER BY fecha_inicio DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerConveniosActivos()
    {
        $db = $this->getConnection();

        $query = "SELECT nombre_empresa
                  FROM convenios
                  WHERE UPPER(estado) = 'ACTIVO'
                  ORDER BY nombre_empresa ASC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();

        $query = "SELECT * FROM convenios WHERE id_convenio = :id";

        $stmt = $db->prepare($query);
        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data)
    {
        $db = $this->getConnection();
        $estados = $this->resolverEstadosPorFechaFin($data);

        $query = "INSERT INTO convenios
        (
            nombre_empresa,
            fecha_inicio,
            fecha_fin,
            estado_convenio,
            tipo_convenio_acuerdo,
            tipo_institucion,
            en_ejecucion,
            tipo_convenio,
            carrera,
            localizacion,
            ciudad,
            observaciones,
            estado
        )
        VALUES
        (
            :nombre_empresa,
            :fecha_inicio,
            :fecha_fin,
            :estado_convenio,
            :tipo_convenio_acuerdo,
            :tipo_institucion,
            :en_ejecucion,
            :tipo_convenio,
            :carrera,
            :localizacion,
            :ciudad,
            :observaciones,
            :estado
        )";

        $stmt = $db->prepare($query);

        return $stmt->execute([
            ':nombre_empresa' => $data['nombre_empresa'],
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin' => $data['fecha_fin'],
            ':estado_convenio' => $estados['estado_convenio'],
            ':tipo_convenio_acuerdo' => $data['tipo_convenio_acuerdo'],
            ':tipo_institucion' => $data['tipo_institucion'],
            ':en_ejecucion' => $data['en_ejecucion'],
            ':tipo_convenio' => $data['tipo_convenio'],
            ':carrera' => $data['carrera'],
            ':localizacion' => $data['localizacion'],
            ':ciudad' => $data['ciudad'],
            ':observaciones' => $data['observaciones'],
            ':estado' => $estados['estado']
        ]);
    }

    public function actualizar($id, $data)
    {
        $db = $this->getConnection();
        $estados = $this->resolverEstadosPorFechaFin($data);

        $query = "UPDATE convenios SET

            nombre_empresa = :nombre_empresa,
            fecha_inicio = :fecha_inicio,
            fecha_fin = :fecha_fin,
            estado_convenio = :estado_convenio,
            tipo_convenio_acuerdo = :tipo_convenio_acuerdo,
            tipo_institucion = :tipo_institucion,
            en_ejecucion = :en_ejecucion,
            tipo_convenio = :tipo_convenio,
            carrera = :carrera,
            localizacion = :localizacion,
            ciudad = :ciudad,
            observaciones = :observaciones,
            estado = :estado

        WHERE id_convenio = :id";

        $stmt = $db->prepare($query);

        return $stmt->execute([
            ':id' => $id,
            ':nombre_empresa' => $data['nombre_empresa'],
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin' => $data['fecha_fin'],
            ':estado_convenio' => $estados['estado_convenio'],
            ':tipo_convenio_acuerdo' => $data['tipo_convenio_acuerdo'],
            ':tipo_institucion' => $data['tipo_institucion'],
            ':en_ejecucion' => $data['en_ejecucion'],
            ':tipo_convenio' => $data['tipo_convenio'],
            ':carrera' => $data['carrera'],
            ':localizacion' => $data['localizacion'],
            ':ciudad' => $data['ciudad'],
            ':observaciones' => $data['observaciones'],
            ':estado' => $estados['estado']
        ]);
    }

    public function eliminar($id)
    {
        $db = $this->getConnection();
        $query = "DELETE FROM convenios WHERE id_convenio = :id";
        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error eliminar convenio: " . $e->getMessage());
            return false;
        }
    }
}
