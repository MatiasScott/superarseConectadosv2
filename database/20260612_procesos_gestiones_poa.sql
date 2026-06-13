CREATE TABLE IF NOT EXISTS procesos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    estado TINYINT(1) DEFAULT 1,
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS procesos_institucionales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    estado TINYINT(1) DEFAULT 1,
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS gestion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    procesos_institucionales_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    estado TINYINT(1) DEFAULT 1,
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gestion_procesos
        FOREIGN KEY (procesos_institucionales_id)
        REFERENCES procesos_institucionales(id)
);

ALTER TABLE poa_actividades
    ADD COLUMN procesos_institucionales_id INT NULL,
    ADD COLUMN gestion_id INT NULL;

ALTER TABLE poa_actividades
    ADD CONSTRAINT fk_poa_actividades_proceso
        FOREIGN KEY (procesos_institucionales_id)
        REFERENCES procesos_institucionales(id),
    ADD CONSTRAINT fk_poa_actividades_gestion
        FOREIGN KEY (gestion_id)
        REFERENCES gestion(id);
