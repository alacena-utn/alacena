-- Tanda 2 del estilo Heladera: disposición de la pantalla por usuario e ingrediente "Bife".
-- Solo hace falta si ya tenías la base creada (con Docker también sirve: docker compose down -v && docker compose up -d).
--   mysql -u alacena -p alacena < database/migraciones/002-tanda-heladera-2.sql
-- Si todavía no corriste la 001, corré primero esa.

ALTER TABLE usuario ADD COLUMN IF NOT EXISTS disposicion VARCHAR(12) NOT NULL DEFAULT 'normal' AFTER color;

INSERT IGNORE INTO ingrediente (id, nombre, categoria_id, unidad_default, gramos_por_unidad, es_basico, perecedero, vida_util_dias)
VALUES (44, 'Bife', 4, 'u', 200, FALSE, TRUE, 3);
INSERT IGNORE INTO sinonimo (ingrediente_id, texto, region) VALUES (44, 'churrasco', 'AR'), (44, 'filete', NULL);
