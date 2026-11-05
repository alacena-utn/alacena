-- Incremento 2 · entrega A: 31 ingredientes nuevos (con sinónimos) y sustituciones de ingredientes.
-- Solo si ya tenías la base (si no: docker compose down -v && docker compose up -d).
--   mysql -u cooki -p cooki < database/migraciones/005-incremento-2-a.sql

-- Incremento 2: 31 ingredientes nuevos (77 a 107), cada uno con su dibujo, para el recetario ampliado.
INSERT IGNORE INTO ingrediente (id, nombre, categoria_id, unidad_default, gramos_por_unidad, es_basico, perecedero, vida_util_dias) VALUES
 (77, 'Orégano', 7, 'g', NULL, FALSE, FALSE, 365), (78, 'Albahaca', 1, 'g', NULL, FALSE, TRUE, 5),
 (79, 'Laurel', 7, 'u', NULL, FALSE, FALSE, 365), (80, 'Comino', 7, 'g', NULL, FALSE, FALSE, 365),
 (81, 'Nuez moscada', 7, 'g', NULL, FALSE, FALSE, 365), (82, 'Vinagre', 6, 'ml', NULL, FALSE, FALSE, 365),
 (83, 'Mostaza', 6, 'g', NULL, FALSE, FALSE, 180), (84, 'Levadura', 6, 'g', NULL, FALSE, TRUE, 20),
 (85, 'Polvo de hornear', 6, 'g', NULL, FALSE, FALSE, 365), (86, 'Avena', 6, 'g', NULL, FALSE, FALSE, 365),
 (87, 'Garbanzo', 8, 'g', NULL, FALSE, FALSE, 365), (88, 'Repollo', 1, 'u', 1500, FALSE, TRUE, 14),
 (89, 'Acelga', 1, 'u', 500, FALSE, TRUE, 5), (90, 'Puerro', 1, 'u', 150, FALSE, TRUE, 10),
 (91, 'Apio', 1, 'u', 50, FALSE, TRUE, 10), (92, 'Panceta', 4, 'g', NULL, FALSE, TRUE, 10),
 (93, 'Carne para guiso', 4, 'g', NULL, FALSE, TRUE, 3), (94, 'Cerdo', 4, 'g', NULL, FALSE, TRUE, 3),
 (95, 'Queso crema', 3, 'g', NULL, FALSE, TRUE, 15), (96, 'Dulce de leche', 6, 'g', NULL, FALSE, FALSE, 90),
 (97, 'Dulce de membrillo', 6, 'g', NULL, FALSE, FALSE, 90), (98, 'Maicena', 6, 'g', NULL, FALSE, FALSE, 365),
 (99, 'Cacao', 6, 'g', NULL, FALSE, FALSE, 365), (100, 'Coco rallado', 6, 'g', NULL, FALSE, FALSE, 180),
 (101, 'Nueces', 6, 'g', NULL, FALSE, FALSE, 180), (102, 'Pasas de uva', 6, 'g', NULL, FALSE, FALSE, 180),
 (103, 'Galletitas', 6, 'g', NULL, FALSE, FALSE, 90), (104, 'Aceitunas', 6, 'g', NULL, FALSE, FALSE, 180),
 (105, 'Salsa de soja', 6, 'ml', NULL, FALSE, FALSE, 365), (106, 'Jengibre', 1, 'g', NULL, FALSE, TRUE, 21),
 (107, 'Esencia de vainilla', 6, 'ml', NULL, FALSE, FALSE, 365);

INSERT IGNORE INTO sinonimo (ingrediente_id, texto, region) VALUES
 (84, 'levadura fresca', NULL), (85, 'polvo leudante', NULL), (87, 'garbanzos', NULL), (88, 'col', NULL),
 (90, 'ajo porro', NULL), (92, 'tocino', NULL), (92, 'bacon', NULL), (93, 'carnaza', 'AR'),
 (93, 'cubos de carne', NULL), (94, 'bondiola', 'AR'), (94, 'chancho', 'AR'), (95, 'queso untable', NULL),
 (98, 'almidón de maíz', NULL), (98, 'fécula de maíz', NULL), (99, 'cacao amargo', NULL),
 (97, 'membrillo', NULL), (102, 'uvas pasas', NULL), (103, 'galletas', NULL), (104, 'olivas', NULL),
 (105, 'soya', 'MX'), (106, 'kion', 'PE'), (107, 'vainilla', NULL), (82, 'aceto', NULL);

-- Sustituciones (incremento 2): si falta el primero y el usuario tiene el segundo, la receta igual se
-- puede hacer y se lo avisa ("Sin crema de leche: podés usar leche"). calidad: 1 a 5 (5 = casi igual).
INSERT IGNORE INTO sustitucion (id, ingrediente_id, reemplazo_id, ratio, nota, calidad) VALUES
 (1, 10, 19, 0.80, 'Usá un poco menos de aceite que de manteca.', 3),
 (2, 11, 24, 1.00, 'Leche con una cucharada de manteca.', 3),
 (3, 11, 63, 1.00, 'Yogur natural, sin hervir.', 3),
 (4, 12, 65, 1.00, NULL, 4),
 (5, 65, 12, 1.00, NULL, 4),
 (6, 25, 12, 1.00, 'Rallado grueso o en cubitos.', 2),
 (7, 64, 12, 1.00, 'Pisado con un tenedor.', 2),
 (8, 27, 70, 1.00, 'Pan seco rallado o procesado.', 4),
 (9, 2, 23, 1.00, NULL, 4),
 (10, 23, 2, 1.00, NULL, 3),
 (11, 32, 71, 1.00, 'Tapas de empanada superpuestas.', 3),
 (12, 71, 32, 1.00, 'Tapa de tarta cortada en discos.', 3),
 (13, 1, 7, 1.00, NULL, 3),
 (14, 7, 1, 1.00, NULL, 3),
 (15, 5, 55, 1.00, NULL, 2),
 (16, 35, 7, 1.00, NULL, 3),
 (17, 43, 4, 1.50, 'Tomate rallado.', 3),
 (18, 4, 43, 0.70, 'Para salsas y guisos.', 3),
 (19, 30, 21, 1.00, NULL, 4),
 (20, 21, 30, 1.00, NULL, 4);

-- Etiqueta nueva para los postres y las meriendas del recetario ampliado
INSERT IGNORE INTO etiqueta (id, nombre) VALUES (4, 'dulce');
