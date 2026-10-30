-- Tanda 1 del estilo Heladera: color de la heladera por usuario.
-- Solo hace falta si ya tenías la base creada con el schema.sql anterior
-- (con Docker, también sirve borrar el volumen: docker compose down -v && docker compose up -d).
--   mysql -u alacena -p alacena < database/migraciones/001-tanda-heladera.sql
-- La cantidad exacta de la despensa no necesita cambios: las columnas cantidad y unidad ya existían.

ALTER TABLE usuario ADD COLUMN IF NOT EXISTS color VARCHAR(10) NOT NULL DEFAULT 'verde' AFTER rol;
