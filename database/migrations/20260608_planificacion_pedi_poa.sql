-- ===========================================================================
-- MIGRACION: MODULO DE PLANIFICACION (PEDI + POA + CRONOGRAMAS)
-- Fecha: 2026-06-08
-- Nota: Script versionado para entornos nuevos. No requiere ejecutarse si ya existe.
-- ===========================================================================

SET NAMES utf8mb4;

-- ===========================================================================
-- MODULO 1: ESTRUCTURA DEL PLAN ESTRATEGICO DE DESARROLLO INSTITUCIONAL (PEDI)
-- ===========================================================================

CREATE TABLE IF NOT EXISTS ejes_estrategicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    estado BOOLEAN DEFAULT TRUE,
    avance NUMERIC(5, 2) DEFAULT 0.00,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS objetivos_estrategicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nombre TEXT NOT NULL,
    eje_id INT NOT NULL,
    estado BOOLEAN DEFAULT TRUE,
    avance NUMERIC(5, 2) DEFAULT 0.00,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_objetivos_eje_id
        FOREIGN KEY (eje_id) REFERENCES ejes_estrategicos(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estrategias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nombre TEXT NOT NULL,
    objetivo_estrategico_id INT NOT NULL,
    estado BOOLEAN DEFAULT TRUE,
    avance NUMERIC(5, 2) DEFAULT 0.00,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_estrategias_objetivo_id
        FOREIGN KEY (objetivo_estrategico_id) REFERENCES objetivos_estrategicos(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lineas_base (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estrategia_id INT NOT NULL UNIQUE,
    porcentaje_partida NUMERIC(5, 2) NOT NULL DEFAULT 0.00,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lineas_base_estrategia_id
        FOREIGN KEY (estrategia_id) REFERENCES estrategias(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS metas_linea_base (
    id INT AUTO_INCREMENT PRIMARY KEY,
    linea_base_id INT NOT NULL,
    anio INT NOT NULL,
    porcentaje_esperado NUMERIC(5, 2) NOT NULL,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_metas_linea_base_id
        FOREIGN KEY (linea_base_id) REFERENCES lineas_base(id)
        ON DELETE CASCADE,
    CONSTRAINT uq_linea_base_anio UNIQUE (linea_base_id, anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
-- MODULO 2: PLAN OPERATIVO ANUAL (POA) Y DETALLES DE ACTIVIDADES/PROYECTOS
-- ===========================================================================

CREATE TABLE IF NOT EXISTS sedes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    estado BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procesos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    estado BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS poa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estrategia_id INT NOT NULL,
    sede_id INT NOT NULL,
    anio_planificacion INT NOT NULL,
    presupuesto_total_aprobado NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    estado_aprobacion VARCHAR(30) DEFAULT 'borrador',
    estado BOOLEAN DEFAULT TRUE,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_poa_estrategia_id
        FOREIGN KEY (estrategia_id) REFERENCES estrategias(id),
    CONSTRAINT fk_poa_sede_id
        FOREIGN KEY (sede_id) REFERENCES sedes(id),
    CONSTRAINT uq_poa_estrategia_sede_anio
        UNIQUE (estrategia_id, sede_id, anio_planificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS poa_procesos (
    poa_id INT NOT NULL,
    proceso_id INT NOT NULL,
    PRIMARY KEY (poa_id, proceso_id),
    CONSTRAINT fk_poa_procesos_poa_id
        FOREIGN KEY (poa_id) REFERENCES poa(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_poa_procesos_proceso_id
        FOREIGN KEY (proceso_id) REFERENCES procesos(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS poa_actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poa_id INT NOT NULL,
    tipo_registro VARCHAR(20) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    laboratorio VARCHAR(100),
    meta TEXT NOT NULL,
    presupuesto_asignado NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    presupuesto_ejecutado NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    avance_actividad NUMERIC(5, 2) DEFAULT 0.00,
    estado BOOLEAN DEFAULT TRUE,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_poa_actividades_poa_id
        FOREIGN KEY (poa_id) REFERENCES poa(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cronogramas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poa_actividad_id INT NOT NULL,
    mes INT NOT NULL CHECK (mes BETWEEN 1 AND 12),
    avance NUMERIC(5, 2) DEFAULT 0.00,
    estado_semaforo VARCHAR(30) DEFAULT 'no_cumple',
    estado BOOLEAN DEFAULT TRUE,
    observaciones TEXT,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cronogramas_actividad_id
        FOREIGN KEY (poa_actividad_id) REFERENCES poa_actividades(id)
        ON DELETE CASCADE,
    CONSTRAINT uq_actividad_mes UNIQUE (poa_actividad_id, mes),
    CONSTRAINT chk_semaforo
        CHECK (estado_semaforo IN ('no_cumple', 'cumple_parcialmente', 'cumple_segun_planificado'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
