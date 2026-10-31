-- Tanda 4 del estilo Heladera: ingredientes propios de cada usuario y respuestas a las consultas.
-- Solo hace falta si ya tenías la base creada (con Docker también sirve: docker compose down -v && docker compose up -d).
--   mysql -u alacena -p alacena < database/migraciones/004-tanda-heladera-4.sql
-- Antes tienen que estar corridas la 001, la 002 y la 003.

-- Ingredientes propios: usuario_id NULL = lista general. El nombre ya no es único en toda la tabla,
-- sino dentro de la lista general y dentro de los propios de cada usuario (columna "ambito").
ALTER TABLE ingrediente
    ADD COLUMN IF NOT EXISTS usuario_id INT UNSIGNED NULL AFTER vida_util_dias,
    ADD COLUMN IF NOT EXISTS ambito INT UNSIGNED AS (IFNULL(usuario_id, 0)) PERSISTENT AFTER usuario_id;
ALTER TABLE ingrediente DROP INDEX IF EXISTS nombre;
ALTER TABLE ingrediente ADD UNIQUE KEY IF NOT EXISTS uq_ingrediente_nombre (nombre, ambito);
ALTER TABLE ingrediente
    ADD CONSTRAINT fk_ingrediente_usuario FOREIGN KEY IF NOT EXISTS (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE;

-- Respuestas a las consultas de contacto
ALTER TABLE contacto
    ADD COLUMN IF NOT EXISTS respuesta TEXT NULL AFTER creado_en,
    ADD COLUMN IF NOT EXISTS respondida_en DATETIME NULL AFTER respuesta,
    ADD COLUMN IF NOT EXISTS respondida_por INT UNSIGNED NULL AFTER respondida_en,
    ADD COLUMN IF NOT EXISTS respuesta_enviada BOOLEAN NOT NULL DEFAULT FALSE AFTER respondida_por;
ALTER TABLE contacto
    ADD CONSTRAINT fk_contacto_respondio FOREIGN KEY IF NOT EXISTS (respondida_por) REFERENCES usuario(id) ON DELETE SET NULL;
