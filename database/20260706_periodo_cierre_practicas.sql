ALTER TABLE practicas_estudiantes
    ADD COLUMN periodo_cierre_practica VARCHAR(100) NULL
    AFTER fecha_fin;

CREATE INDEX idx_practicas_periodo_cierre
    ON practicas_estudiantes (periodo_cierre_practica);
