-- Incremento 2 · entrega B: recuperar la contraseña por correo y bloqueo por intentos de ingreso.
-- Solo si ya tenías la base (si no: docker compose down -v && docker compose up -d).
--   mysql -u cooki -p cooki < database/migraciones/006-incremento-2-b.sql
-- Las tablas del modo cocina, las preferencias y las favoritas ya estaban en schema.sql desde el incremento 1.

CREATE TABLE IF NOT EXISTS recuperacion (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    token_hash  CHAR(64)     NOT NULL UNIQUE,
    vence_en    DATETIME     NOT NULL,
    usado_en    DATETIME     NULL,
    creado_en   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_recuperacion_usuario (usuario_id, creado_en),
    CONSTRAINT fk_recuperacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS intento_ingreso (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(190) NOT NULL,
    ip         VARCHAR(45)  NOT NULL,
    creado_en  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_intento_email (email, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
