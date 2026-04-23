-- ============================================================
-- Tabla para almacenar los datos del Plan de Aprendizaje
-- que el estudiante llena en el formulario.
-- Ejecutar este script en la base de datos de la aplicación.
-- ============================================================

CREATE TABLE IF NOT EXISTS plan_aprendizaje_datos (
    id                          INT AUTO_INCREMENT PRIMARY KEY,
    practica_id                 INT NOT NULL,

    -- Datos del estudiante
    apellidos_nombres           VARCHAR(255)  DEFAULT NULL,
    carrera                     VARCHAR(255)  DEFAULT NULL,
    nivel                       VARCHAR(100)  DEFAULT NULL,
    cedula                      VARCHAR(20)   DEFAULT NULL,
    correo                      VARCHAR(255)  DEFAULT NULL,
    telefono                    VARCHAR(50)   DEFAULT NULL,

    -- Datos de la empresa
    nombre_empresa              VARCHAR(255)  DEFAULT NULL,
    ruc                         VARCHAR(20)   DEFAULT NULL,
    tipo_entidad                VARCHAR(100)  DEFAULT NULL,
    actividad_economica         VARCHAR(255)  DEFAULT NULL,
    ubicacion                   VARCHAR(255)  DEFAULT NULL,
    area_departamento           VARCHAR(255)  DEFAULT NULL,
    nombre_tutor_empresarial    VARCHAR(255)  DEFAULT NULL,
    telefono_tutor_empresarial  VARCHAR(50)   DEFAULT NULL,
    correo_tutor_empresarial    VARCHAR(255)  DEFAULT NULL,
    descripcion_empresa         TEXT          DEFAULT NULL,

    -- Datos del período de prácticas
    periodo_academico           VARCHAR(100)  DEFAULT NULL,
    fecha_inicio                VARCHAR(50)   DEFAULT NULL,
    fecha_fin                   VARCHAR(50)   DEFAULT NULL,
    horario                     VARCHAR(255)  DEFAULT NULL,
    total_horas                 VARCHAR(20)   DEFAULT NULL,
    modalidad                   VARCHAR(100)  DEFAULT NULL,

    -- Tutor académico
    nombre_tutor_academico      VARCHAR(255)  DEFAULT NULL,
    correo_tutor_academico      VARCHAR(255)  DEFAULT NULL,

    -- Resultados de aprendizaje seleccionados (1 = seleccionado, 0 = no)
    ra1                         TINYINT(1)    NOT NULL DEFAULT 0,
    ra2                         TINYINT(1)    NOT NULL DEFAULT 0,
    ra3                         TINYINT(1)    NOT NULL DEFAULT 0,
    ra4                         TINYINT(1)    NOT NULL DEFAULT 0,
    ra5                         TINYINT(1)    NOT NULL DEFAULT 0,
    ra6                         TINYINT(1)    NOT NULL DEFAULT 0,
    ra7                         TINYINT(1)    NOT NULL DEFAULT 0,
    ra8                         TINYINT(1)    NOT NULL DEFAULT 0,
    ra9                         TINYINT(1)    NOT NULL DEFAULT 0,

    -- Firmas (imágenes en base64)
    signature_tutor_empresarial LONGTEXT      DEFAULT NULL,
    signature_tutor_academico   LONGTEXT      DEFAULT NULL,
    nombre_firma_empresarial    VARCHAR(255)  DEFAULT NULL,
    nombre_firma_academico      VARCHAR(255)  DEFAULT NULL,

    created_at                  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_practica_plan (practica_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
