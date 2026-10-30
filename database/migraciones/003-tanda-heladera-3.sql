-- Tanda 3 del estilo Heladera: volumen de los sonidos por usuario, "Pollo" sin "(pechuga)"
-- y 32 ingredientes nuevos (frutas, verduras, lácteos, carnes y almacén), cada uno con su dibujo.
-- Solo hace falta si ya tenías la base creada (con Docker también sirve: docker compose down -v && docker compose up -d).
--   mysql -u alacena -p alacena < database/migraciones/003-tanda-heladera-3.sql
-- Antes tienen que estar corridas la 001 y la 002.

ALTER TABLE usuario ADD COLUMN IF NOT EXISTS volumen TINYINT UNSIGNED NOT NULL DEFAULT 50 AFTER disposicion;

UPDATE ingrediente SET nombre = 'Pollo' WHERE id = 14 AND nombre = 'Pollo (pechuga)';

INSERT IGNORE INTO ingrediente (id, nombre, categoria_id, unidad_default, gramos_por_unidad, es_basico, perecedero, vida_util_dias) VALUES
 (45, 'Manzana', 2, 'u', 180, FALSE, TRUE, 30),
 (46, 'Naranja', 2, 'u', 200, FALSE, TRUE, 21),
 (47, 'Pera', 2, 'u', 170, FALSE, TRUE, 14),
 (48, 'Durazno', 2, 'u', 150, FALSE, TRUE, 5),
 (49, 'Uva', 2, 'g', NULL, FALSE, TRUE, 7),
 (50, 'Ananá', 2, 'u', 1500, FALSE, TRUE, 7),
 (51, 'Kiwi', 2, 'u', 80, FALSE, TRUE, 14),
 (52, 'Sandía', 2, 'u', 4000, FALSE, TRUE, 10),
 (53, 'Mandarina', 2, 'u', 120, FALSE, TRUE, 14),
 (54, 'Cereza', 2, 'g', NULL, FALSE, TRUE, 5),
 (55, 'Berenjena', 1, 'u', 300, FALSE, TRUE, 10),
 (56, 'Brócoli', 1, 'u', 400, FALSE, TRUE, 7),
 (57, 'Espinaca', 1, 'g', NULL, FALSE, TRUE, 5),
 (58, 'Pepino', 1, 'u', 250, FALSE, TRUE, 10),
 (59, 'Champiñón', 1, 'g', NULL, FALSE, TRUE, 6),
 (60, 'Remolacha', 1, 'u', 150, FALSE, TRUE, 21),
 (61, 'Coliflor', 1, 'u', 800, FALSE, TRUE, 10),
 (62, 'Rabanito', 1, 'u', 20, FALSE, TRUE, 7),
 (63, 'Yogur', 3, 'ml', NULL, FALSE, TRUE, 21),
 (64, 'Ricota', 3, 'g', NULL, FALSE, TRUE, 7),
 (65, 'Mozzarella', 3, 'g', NULL, FALSE, TRUE, 14),
 (66, 'Salchicha', 4, 'u', 50, FALSE, TRUE, 14),
 (67, 'Chorizo', 4, 'u', 120, FALSE, TRUE, 5),
 (68, 'Merluza', 4, 'u', 150, FALSE, TRUE, 2),
 (69, 'Langostinos', 4, 'g', NULL, FALSE, TRUE, 2),
 (70, 'Pan', 6, 'g', NULL, FALSE, TRUE, 3),
 (71, 'Tapas de empanada', 6, 'u', NULL, FALSE, TRUE, 20),
 (72, 'Mayonesa', 6, 'g', NULL, FALSE, FALSE, 90),
 (73, 'Miel', 6, 'g', NULL, FALSE, FALSE, 730),
 (74, 'Chocolate', 6, 'g', NULL, FALSE, FALSE, 365),
 (75, 'Yerba', 6, 'g', NULL, FALSE, FALSE, 365),
 (76, 'Café', 6, 'g', NULL, FALSE, FALSE, 365);

INSERT IGNORE INTO sinonimo (ingrediente_id, texto, region) VALUES
 (14, 'pechuga', NULL), (48, 'melocotón', 'ES'), (50, 'piña', NULL), (53, 'tangerina', NULL), (59, 'hongo', NULL),
 (60, 'betabel', 'MX'), (62, 'rábano', NULL), (63, 'yogurt', NULL), (64, 'requesón', NULL), (65, 'muzzarella', 'AR'),
 (68, 'pescado', NULL), (69, 'camarones', NULL), (71, 'discos de empanada', NULL);
