<?php
require_once __DIR__ . '/Database.php';

class ConfiguracionPlanificacionModel extends Database
{
    public function obtenerProcesos(): array
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT id, nombre, estado FROM procesos ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function obtenerProcesoPorId(int $id): ?array
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT id, nombre, estado FROM procesos WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crearProceso(string $nombre, bool $estado): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("INSERT INTO procesos (nombre, estado) VALUES (?, ?)");
        return $stmt->execute([trim($nombre), $estado ? 1 : 0]);
    }

    public function actualizarProceso(int $id, string $nombre, bool $estado): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("UPDATE procesos SET nombre = ?, estado = ? WHERE id = ?");
        return $stmt->execute([trim($nombre), $estado ? 1 : 0, $id]);
    }

    public function eliminarProceso(int $id): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("DELETE FROM procesos WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function procesoEnUso(int $id): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM poa_procesos WHERE proceso_id = ?");
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function obtenerEjes(): array
    {
        $db = $this->getConnection();
        $sql = "SELECT
                    e.id,
                    e.nombre,
                    e.observaciones,
                    e.estado,
                    e.avance,
                    e.fecha_creacion,
                    COALESCE(COUNT(o.id), 0) AS total_objetivos
                FROM ejes_estrategicos e
                LEFT JOIN objetivos_estrategicos o ON o.eje_id = e.id
                GROUP BY e.id
                ORDER BY e.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function obtenerEjePorId(int $id): ?array
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT id, nombre, observaciones, estado, avance FROM ejes_estrategicos WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crearEje(array $data): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("INSERT INTO ejes_estrategicos (nombre, observaciones, estado) VALUES (?, ?, ?)");
        return $stmt->execute([
            trim((string) ($data['nombre'] ?? '')),
            trim((string) ($data['observaciones'] ?? '')),
            !empty($data['estado']) ? 1 : 0,
        ]);
    }

    public function actualizarEje(int $id, array $data): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("UPDATE ejes_estrategicos SET nombre = ?, observaciones = ?, estado = ? WHERE id = ?");
        return $stmt->execute([
            trim((string) ($data['nombre'] ?? '')),
            trim((string) ($data['observaciones'] ?? '')),
            !empty($data['estado']) ? 1 : 0,
            $id,
        ]);
    }

    public function eliminarEje(int $id): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("DELETE FROM ejes_estrategicos WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function ejeEnUsoEnPoaActivo(int $id): bool
    {
        $db = $this->getConnection();
        $sql = "SELECT COUNT(*)
                FROM poa p
                INNER JOIN estrategias es ON es.id = p.estrategia_id
                INNER JOIN objetivos_estrategicos o ON o.id = es.objetivo_estrategico_id
                WHERE o.eje_id = ?
                  AND p.estado = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function obtenerObjetivos(): array
    {
        $db = $this->getConnection();
        $sql = "SELECT
                    o.id,
                    o.codigo,
                    o.nombre,
                    o.eje_id,
                    o.estado,
                    o.avance,
                    o.observaciones,
                    e.nombre AS eje_nombre,
                    COALESCE(COUNT(es.id), 0) AS total_estrategias
                FROM objetivos_estrategicos o
                INNER JOIN ejes_estrategicos e ON e.id = o.eje_id
                LEFT JOIN estrategias es ON es.objetivo_estrategico_id = o.id
                GROUP BY o.id
                ORDER BY o.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function obtenerObjetivoPorId(int $id): ?array
    {
        $db = $this->getConnection();
        $sql = "SELECT id, codigo, nombre, eje_id, estado, avance, observaciones
                FROM objetivos_estrategicos
                WHERE id = ?
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crearObjetivo(array $data): bool
    {
        $db = $this->getConnection();
        $sql = "INSERT INTO objetivos_estrategicos (codigo, nombre, eje_id, observaciones, estado)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            trim((string) ($data['codigo'] ?? '')),
            trim((string) ($data['nombre'] ?? '')),
            (int) ($data['eje_id'] ?? 0),
            trim((string) ($data['observaciones'] ?? '')),
            !empty($data['estado']) ? 1 : 0,
        ]);
    }

    public function actualizarObjetivo(int $id, array $data): bool
    {
        $db = $this->getConnection();
        $sql = "UPDATE objetivos_estrategicos
                SET codigo = ?, nombre = ?, eje_id = ?, observaciones = ?, estado = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            trim((string) ($data['codigo'] ?? '')),
            trim((string) ($data['nombre'] ?? '')),
            (int) ($data['eje_id'] ?? 0),
            trim((string) ($data['observaciones'] ?? '')),
            !empty($data['estado']) ? 1 : 0,
            $id,
        ]);
    }

    public function eliminarObjetivo(int $id): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("DELETE FROM objetivos_estrategicos WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function objetivoEnUsoEnPoaActivo(int $id): bool
    {
        $db = $this->getConnection();
        $sql = "SELECT COUNT(*)
                FROM poa p
                INNER JOIN estrategias es ON es.id = p.estrategia_id
                WHERE es.objetivo_estrategico_id = ?
                  AND p.estado = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function obtenerEstrategias(): array
    {
        $db = $this->getConnection();
        $sql = "SELECT
                    es.id,
                    es.codigo,
                    es.nombre,
                    es.objetivo_estrategico_id,
                    es.estado,
                    es.avance,
                    es.observaciones,
                    obj.codigo AS objetivo_codigo,
                    obj.nombre AS objetivo_nombre,
                    ej.nombre AS eje_nombre,
                    lb.id AS linea_base_id,
                    lb.porcentaje_partida,
                    lb.observaciones AS linea_base_observaciones
                FROM estrategias es
                INNER JOIN objetivos_estrategicos obj ON obj.id = es.objetivo_estrategico_id
                INNER JOIN ejes_estrategicos ej ON ej.id = obj.eje_id
                LEFT JOIN lineas_base lb ON lb.estrategia_id = es.id
                ORDER BY es.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function obtenerEstrategiaDetallePorId(int $id): ?array
    {
        $db = $this->getConnection();
        $sql = "SELECT
                    es.id,
                    es.codigo,
                    es.nombre,
                    es.objetivo_estrategico_id,
                    es.estado,
                    es.avance,
                    es.observaciones,
                    lb.id AS linea_base_id,
                    lb.porcentaje_partida,
                    lb.observaciones AS linea_base_observaciones
                FROM estrategias es
                LEFT JOIN lineas_base lb ON lb.estrategia_id = es.id
                WHERE es.id = ?
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $row['metas'] = $this->obtenerMetasPorEstrategia($id);
        return $row;
    }

    public function obtenerMetasPorEstrategia(int $estrategiaId): array
    {
        $db = $this->getConnection();
        $sql = "SELECT
                    m.id,
                    m.linea_base_id,
                    m.anio,
                    m.porcentaje_esperado,
                    m.observaciones
                FROM metas_linea_base m
                INNER JOIN lineas_base lb ON lb.id = m.linea_base_id
                WHERE lb.estrategia_id = ?
                ORDER BY m.anio ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$estrategiaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function crearEstrategiaConDetalle(array $estrategiaData, array $lineaBaseData, array $metas): bool
    {
        $db = $this->getConnection();
        $db->beginTransaction();

        try {
            $stmtEstrategia = $db->prepare(
                "INSERT INTO estrategias (codigo, nombre, objetivo_estrategico_id, observaciones, estado)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmtEstrategia->execute([
                trim((string) ($estrategiaData['codigo'] ?? '')),
                trim((string) ($estrategiaData['nombre'] ?? '')),
                (int) ($estrategiaData['objetivo_estrategico_id'] ?? 0),
                trim((string) ($estrategiaData['observaciones'] ?? '')),
                !empty($estrategiaData['estado']) ? 1 : 0,
            ]);

            $estrategiaId = (int) $db->lastInsertId();

            $stmtLineaBase = $db->prepare(
                "INSERT INTO lineas_base (estrategia_id, porcentaje_partida, observaciones)
                 VALUES (?, ?, ?)"
            );
            $stmtLineaBase->execute([
                $estrategiaId,
                (float) ($lineaBaseData['porcentaje_partida'] ?? 0),
                trim((string) ($lineaBaseData['observaciones'] ?? '')),
            ]);

            $lineaBaseId = (int) $db->lastInsertId();
            $this->insertarMetasLineaBase($db, $lineaBaseId, $metas);

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('Error crear estrategia con detalle: ' . $e->getMessage());
            return false;
        }
    }

    public function actualizarEstrategiaConDetalle(int $estrategiaId, array $estrategiaData, array $lineaBaseData, array $metas): bool
    {
        $db = $this->getConnection();
        $db->beginTransaction();

        try {
            $stmtEstrategia = $db->prepare(
                "UPDATE estrategias
                 SET codigo = ?, nombre = ?, objetivo_estrategico_id = ?, observaciones = ?, estado = ?
                 WHERE id = ?"
            );
            $stmtEstrategia->execute([
                trim((string) ($estrategiaData['codigo'] ?? '')),
                trim((string) ($estrategiaData['nombre'] ?? '')),
                (int) ($estrategiaData['objetivo_estrategico_id'] ?? 0),
                trim((string) ($estrategiaData['observaciones'] ?? '')),
                !empty($estrategiaData['estado']) ? 1 : 0,
                $estrategiaId,
            ]);

            $lineaBaseId = $this->obtenerLineaBaseIdPorEstrategia($db, $estrategiaId);
            if ($lineaBaseId > 0) {
                $stmtLineaBase = $db->prepare(
                    "UPDATE lineas_base
                     SET porcentaje_partida = ?, observaciones = ?
                     WHERE id = ?"
                );
                $stmtLineaBase->execute([
                    (float) ($lineaBaseData['porcentaje_partida'] ?? 0),
                    trim((string) ($lineaBaseData['observaciones'] ?? '')),
                    $lineaBaseId,
                ]);
            } else {
                $stmtLineaBase = $db->prepare(
                    "INSERT INTO lineas_base (estrategia_id, porcentaje_partida, observaciones)
                     VALUES (?, ?, ?)"
                );
                $stmtLineaBase->execute([
                    $estrategiaId,
                    (float) ($lineaBaseData['porcentaje_partida'] ?? 0),
                    trim((string) ($lineaBaseData['observaciones'] ?? '')),
                ]);
                $lineaBaseId = (int) $db->lastInsertId();
            }

            $stmtDeleteMetas = $db->prepare("DELETE FROM metas_linea_base WHERE linea_base_id = ?");
            $stmtDeleteMetas->execute([$lineaBaseId]);

            $this->insertarMetasLineaBase($db, $lineaBaseId, $metas);

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('Error actualizar estrategia con detalle: ' . $e->getMessage());
            return false;
        }
    }

    public function eliminarEstrategia(int $id): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("DELETE FROM estrategias WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function estrategiaEnUsoEnPoaActivo(int $id): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM poa WHERE estrategia_id = ? AND estado = 1");
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function obtenerLineaBaseIdPorEstrategia(PDO $db, int $estrategiaId): int
    {
        $stmt = $db->prepare("SELECT id FROM lineas_base WHERE estrategia_id = ? LIMIT 1");
        $stmt->execute([$estrategiaId]);
        return (int) $stmt->fetchColumn();
    }

    private function insertarMetasLineaBase(PDO $db, int $lineaBaseId, array $metas): void
    {
        if (empty($metas)) {
            return;
        }

        $stmtMeta = $db->prepare(
            "INSERT INTO metas_linea_base (linea_base_id, anio, porcentaje_esperado, observaciones)
             VALUES (?, ?, ?, ?)"
        );

        foreach ($metas as $meta) {
            $stmtMeta->execute([
                $lineaBaseId,
                (int) ($meta['anio'] ?? 0),
                (float) ($meta['porcentaje_esperado'] ?? 0),
                trim((string) ($meta['observaciones'] ?? '')),
            ]);
        }
    }
}
