-- Creación de tablas
CREATE TABLE IF NOT EXISTS departamentos (
  id_departamento INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provincias (
  id_provincia INT AUTO_INCREMENT PRIMARY KEY,
  id_departamento INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  FOREIGN KEY (id_departamento) REFERENCES departamentos(id_departamento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS distritos (
  id_distrito INT AUTO_INCREMENT PRIMARY KEY,
  id_provincia INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  FOREIGN KEY (id_provincia) REFERENCES provincias(id_provincia) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limpiar tablas si existen datos previos (opcional, para reiniciar)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE distritos;
TRUNCATE TABLE provincias;
TRUNCATE TABLE departamentos;
SET FOREIGN_KEY_CHECKS = 1;

-- Insertar Departamentos
INSERT INTO departamentos (id_departamento, nombre) VALUES
(1, 'Beni'),
(2, 'Chuquisaca'),
(3, 'Cochabamba'),
(4, 'La Paz'),
(5, 'Oruro'),
(6, 'Pando'),
(7, 'Potosí'),
(8, 'Santa Cruz'),
(9, 'Tarija');

-- Insertar Provincias y Distritos (Muestra representativa y completa por departamento)

-- BENI (ID: 1)
INSERT INTO provincias (id_provincia, id_departamento, nombre) VALUES
(1, 1, 'Cercado'), (2, 1, 'Vaca Díez'), (3, 1, 'José Ballivián'), (4, 1, 'Yacuma'), (5, 1, 'Moxos'), (6, 1, 'Marbán'), (7, 1, 'Mamoré'), (8, 1, 'Iténez');

INSERT INTO distritos (id_provincia, nombre) VALUES
(1, 'Trinidad'), (1, 'San Javier'),
(2, 'Riberalta'), (2, 'Guayaramerín'),
(3, 'Reyes'), (3, 'San Borja'), (3, 'Santa Rosa'), (3, 'Rurrenabaque'),
(4, 'Santa Ana de Yacuma'), (4, 'Exaltación'),
(5, 'San Ignacio de Moxos'),
(6, 'Loreto'), (6, 'San Andrés'),
(7, 'San Joaquín'), (7, 'San Ramón'), (7, 'Puerto Siles'),
(8, 'Magdalena'), (8, 'Baures'), (8, 'Huacaraje');

-- CHUQUISACA (ID: 2)
INSERT INTO provincias (id_provincia, id_departamento, nombre) VALUES
(9, 2, 'Oropeza'), (10, 2, 'Azurduy'), (11, 2, 'Zudáñez'), (12, 2, 'Tomina'), (13, 2, 'Hernando Siles'), (14, 2, 'Yamparáez'), (15, 2, 'Nor Cinti'), (16, 2, 'Sud Cinti'), (17, 2, 'Belisario Boeto'), (18, 2, 'Luis Calvo');

INSERT INTO distritos (id_provincia, nombre) VALUES
(9, 'Sucre'), (9, 'Yotala'), (9, 'Poroma'),
(10, 'Azurduy'), (10, 'Tarvita'),
(11, 'Zudáñez'), (11, 'Presto'), (11, 'Mojocoya'), (11, 'Icla'),
(12, 'Padilla'), (12, 'Tomina'), (12, 'Sopachuy'), (12, 'Villa Alcalá'), (12, 'El Villar'),
(13, 'Monteagudo'), (13, 'Huacareta'),
(14, 'Tarabuco'), (14, 'Yamparáez'),
(15, 'Camargo'), (15, 'San Lucas'), (15, 'Incahuasi'), (15, 'Villa Charcas'),
(16, 'Villa Abecia'), (16, 'Culpina'), (16, 'Las Carreras'),
(17, 'Villa Serrano'),
(18, 'Villa Vaca Guzmán'), (18, 'Huacaya'), (18, 'Macharetí');

-- COCHABAMBA (ID: 3)
INSERT INTO provincias (id_provincia, id_departamento, nombre) VALUES
(19, 3, 'Arani'), (20, 3, 'Arque'), (21, 3, 'Ayopaya'), (22, 3, 'Bolívar'), (23, 3, 'Campero'), (24, 3, 'Capinota'), (25, 3, 'Cercado'), (26, 3, 'Carrasco'), (27, 3, 'Chapare'), (28, 3, 'Esteban Arce'), (29, 3, 'Germán Jordán'), (30, 3, 'Mizque'), (31, 3, 'Punata'), (32, 3, 'Quillacollo'), (33, 3, 'Tapacarí'), (34, 3, 'Tiraque');

INSERT INTO distritos (id_provincia, nombre) VALUES
(19, 'Arani'), (19, 'Vacas'),
(20, 'Arque'), (20, 'Tacopaya'),
(21, 'Ayopaya'), (21, 'Morochata'), (21, 'Cochabamba'),
(22, 'Bolívar'),
(23, 'Aiquile'), (23, 'Pasorapa'), (23, 'Omereque'),
(24, 'Capinota'), (24, 'Santiváñez'), (24, 'Sicaya'),
(25, 'Cochabamba'),
(26, 'Totora'), (26, 'Pojo'), (26, 'Pocona'), (26, 'Chimoré'), (26, 'Puerto Villarroel'), (26, 'Entre Ríos'),
(27, 'Sacaba'), (27, 'Colomi'), (27, 'Villa Tunari'),
(28, 'Tarata'), (28, 'Anzaldo'), (28, 'Arbieto'), (28, 'Sacabamba'),
(29, 'Cliza'), (29, 'Toco'), (29, 'Tolata'),
(30, 'Mizque'), (30, 'Vila Vila'), (30, 'Alalay'),
(31, 'Punata'), (31, 'Villa Rivero'), (31, 'San Benito'), (31, 'Tacachi'), (31, 'Cuchumuela'),
(32, 'Quillacollo'), (32, 'Sipe Sipe'), (32, 'Tiquipaya'), (32, 'Vinto'), (32, 'Colcapirhua'),
(33, 'Tapacarí'),
(34, 'Tiraque'), (34, 'Shinahota');

-- LA PAZ (ID: 4)
INSERT INTO provincias (id_provincia, id_departamento, nombre) VALUES
(35, 4, 'Aroma'), (36, 4, 'Bautista Saavedra'), (37, 4, 'Abel Iturralde'), (38, 4, 'Caranavi'), (39, 4, 'Eliodoro Camacho'), (40, 4, 'Franz Tamayo'), (41, 4, 'Gualberto Villarroel'), (42, 4, 'Ingavi'), (43, 4, 'Inquisivi'), (44, 4, 'José Manuel Pando'), (45, 4, 'Larecaja'), (46, 4, 'Loayza'), (47, 4, 'Los Andes'), (48, 4, 'Manco Kapac'), (49, 4, 'Muñecas'), (50, 4, 'Nor Yungas'), (51, 4, 'Omasuyos'), (52, 4, 'Pacajes'), (53, 4, 'Pedro Domingo Murillo'), (54, 4, 'Sud Yungas');

INSERT INTO distritos (id_provincia, nombre) VALUES
(35, 'Sica Sica'), (35, 'Umala'), (35, 'Ayo Ayo'), (35, 'Calamarca'), (35, 'Patacamaya'), (35, 'Colquencha'), (35, 'Collana'),
(36, 'Charazani'), (36, 'Curva'),
(37, 'Ixiamas'), (37, 'San Buenaventura'),
(38, 'Caranavi'), (38, 'Alto Beni'),
(39, 'Puerto Acosta'), (39, 'Mocomoco'), (39, 'Puerto Carabuco'),
(40, 'Apolo'), (40, 'Pelechuco'),
(41, 'San Pedro de Curahuara'), (41, 'Papel Pampa'), (41, 'Chacarilla'),
(42, 'Viacha'), (42, 'Guaqui'), (42, 'Tiahuanacu'), (42, 'Desaguadero'), (42, 'San Andrés de Machaca'), (42, 'Jesús de Machaca'), (42, 'Taraco'),
(43, 'Inquisivi'), (43, 'Quime'), (43, 'Cajuata'), (43, 'Colquiri'), (43, 'Ichoca'), (43, 'Licoma Pampa'),
(44, 'Santiago de Machaca'), (44, 'Catacora'),
(45, 'Sorata'), (45, 'Guanay'), (45, 'Tacacoma'), (45, 'Quiabaya'), (45, 'Combaya'), (45, 'Tipuani'), (45, 'Mapiri'), (45, 'Teoponte'),
(46, 'Luribay'), (46, 'Sapahaqui'), (46, 'Yaco'), (46, 'Malla'), (46, 'Cairoma'),
(47, 'Pucarani'), (47, 'Laja'), (47, 'Batallas'), (47, 'Puerto Pérez'),
(48, 'Copacabana'), (48, 'San Pedro de Tiquina'), (48, 'Tito Yupanqui'),
(49, 'Chuma'), (49, 'Ayata'), (49, 'Aucapata'),
(50, 'Coroico'), (50, 'Coripata'),
(51, 'Achacachi'), (51, 'Ancoraimes'), (51, 'Chua Cocani'), (51, 'Huarina'),
(52, 'Corocoro'), (52, 'Caquiaviri'), (52, 'Calacoto'), (52, 'Comanche'), (52, 'Charaña'), (52, 'Waldo Ballivián'),
(53, 'La Paz'), (53, 'Palca'), (53, 'Mecapaca'), (53, 'Achocalla'), (53, 'El Alto'),
(54, 'Chulumani'), (54, 'Irupana'), (54, 'Yanacachi'), (54, 'Palos Blancos'), (54, 'La Asunta');

-- ORURO (ID: 5)
INSERT INTO provincias (id_provincia, id_departamento, nombre) VALUES
(55, 5, 'Atahuallpa'), (56, 5, 'Carangas'), (57, 5, 'Cercado'), (58, 5, 'Eduardo Avaroa'), (59, 5, 'Ladislao Cabrera'), (60, 5, 'Litoral'), (61, 5, 'Nor Carangas'), (62, 5, 'Pantaleón Dalence'), (63, 5, 'Poopó'), (64, 5, 'Puerto de Mejillones'), (65, 5, 'Sajama'), (66, 5, 'San Pedro de Totora'), (67, 5, 'Saucarí'), (68, 5, 'Sebastián Pagador'), (69, 5, 'Sud Carangas'), (70, 5, 'Tomas Barrón');

INSERT INTO distritos (id_provincia, nombre) VALUES
(55, 'Sabaya'), (55, 'Coipasa'), (55, 'Chipaya'),
(56, 'Corque'), (56, 'Choquecota'),
(57, 'Oruro'), (57, 'Caracollo'), (57, 'El Choro'), (57, 'Soracachi'),
(58, 'Challapata'), (58, 'Santuario de Quillacas'),
(59, 'Salinas de Garci Mendoza'), (59, 'Pampa Aullagas'),
(60, 'Huachacalla'), (60, 'Esmeralda'), (60, 'Cruz de Machacamarca'), (60, 'Escara'), (60, 'Yunguyo del Litoral'),
(61, 'Huayllamarca'),
(62, 'Huanuni'), (62, 'Machacamarca'),
(63, 'Poopó'), (63, 'Pazña'), (63, 'Antequera'),
(64, 'La Rivera'), (64, 'Todos Santos'), (64, 'Carangas'),
(65, 'Curahuara de Carangas'), (65, 'Turco'),
(66, 'Totora'),
(67, 'Toledo'),
(68, 'Santiago de Huari'),
(69, 'Andamarca'), (69, 'Belén de Andamarca'),
(70, 'Eucaliptus');

-- PANDO (ID: 6)
INSERT INTO provincias (id_provincia, id_departamento, nombre) VALUES
(71, 6, 'Abuná'), (72, 6, 'Federico Román'), (73, 6, 'Madre de Dios'), (74, 6, 'Manuripi'), (75, 6, 'Nicolás Suárez');

INSERT INTO distritos (id_provincia, nombre) VALUES
(71, 'Santa Rosa del Abuná'), (71, 'Ingavi'),
(72, 'Nueva Esperanza'), (72, 'Villa Nueva'), (72, 'Santos Mercado'),
(73, 'Puerto Gonzalo Moreno'), (73, 'San Lorenzo'), (73, 'Sena'),
(74, 'Puerto Rico'), (74, 'San Pedro'), (74, 'Filadelfia'),
(75, 'Cobija'), (75, 'Porvenir'), (75, 'Bolpebra'), (75, 'Bella Flor');

-- POTOSI (ID: 7)
INSERT INTO provincias (id_provincia, id_departamento, nombre) VALUES
(76, 7, 'Alonso de Ibáñez'), (77, 7, 'Antonio Quijarro'), (78, 7, 'Bernardino Bilbao'), (79, 7, 'Charcas'), (80, 7, 'Chayanta'), (81, 7, 'Cornelio Saavedra'), (82, 7, 'Daniel Campos'), (83, 7, 'Enrique Baldivieso'), (84, 7, 'José María Linares'), (85, 7, 'Modesto Omiste'), (86, 7, 'Nor Chichas'), (87, 7, 'Nor Lípez'), (88, 7, 'Rafael Bustillo'), (89, 7, 'Sud Chichas'), (90, 7, 'Sud Lípez'), (91, 7, 'Tomás Frías');

INSERT INTO distritos (id_provincia, nombre) VALUES
(76, 'Sacaca'), (76, 'Caripuyo'),
(77, 'Uyuni'), (77, 'Tomave'), (77, 'Porco'),
(78, 'Arampampa'), (78, 'Acaso'),
(79, 'San Pedro de Buena Vista'), (79, 'Toro Toro'),
(80, 'Colquechaca'), (80, 'Ravelo'), (80, 'Pocoata'), (80, 'Ocurí'),
(81, 'Betanzos'), (81, 'Chaqui'), (81, 'Tacobamba'),
(82, 'Llica'), (82, 'Tahua'),
(83, 'San Agustín'),
(84, 'Puna'), (84, 'Caiza "D"'), (84, 'Ckochas'),
(85, 'Villazón'),
(86, 'Cotagaita'), (86, 'Vitichi'),
(87, 'Colcha "K"'), (87, 'San Pedro de Quemes'),
(88, 'Uncía'), (88, 'Chayanta'), (88, 'Llallagua'), (88, 'Chuquihuta'),
(89, 'Tupiza'), (89, 'Atocha'),
(90, 'San Pablo de Lípez'), (90, 'Mojinete'), (90, 'San Antonio de Esmoruco'),
(91, 'Potosí'), (91, 'Tinguipaya'), (91, 'Yocalla'), (91, 'Urmiri');

-- SANTA CRUZ (ID: 8)
INSERT INTO provincias (id_provincia, id_departamento, nombre) VALUES
(92, 8, 'Andrés Ibáñez'), (93, 8, 'Ignacio Warnes'), (94, 8, 'José Miguel de Velasco'), (95, 8, 'Ichilo'), (96, 8, 'Chiquitos'), (97, 8, 'Sara'), (98, 8, 'Cordillera'), (99, 8, 'Vallegrande'), (100, 8, 'Florida'), (101, 8, 'Obispo Santistevan'), (102, 8, 'Ñuflo de Chávez'), (103, 8, 'Ángel Sandoval'), (104, 8, 'Manuel María Caballero'), (105, 8, 'Germán Busch'), (106, 8, 'Guarayos');

INSERT INTO distritos (id_provincia, nombre) VALUES
(92, 'Santa Cruz de la Sierra'), (92, 'Cotoca'), (92, 'Porongo'), (92, 'La Guardia'), (92, 'El Torno'),
(93, 'Warnes'), (93, 'Okinawa Uno'),
(94, 'San Ignacio de Velasco'), (94, 'San Miguel de Velasco'), (94, 'San Rafael'),
(95, 'Buena Vista'), (95, 'San Carlos'), (95, 'Yapacaní'), (95, 'San Juan de Yapacaní'),
(96, 'San José de Chiquitos'), (96, 'Pailón'), (96, 'Roboré'),
(97, 'Portachuelo'), (97, 'Santa Rosa del Sara'), (97, 'Colpa Bélgica'),
(98, 'Lagunillas'), (98, 'Charagua'), (98, 'Cabezas'), (98, 'Cuevo'), (98, 'Gutiérrez'), (98, 'Camiri'), (98, 'Boyuibe'),
(99, 'Vallegrande'), (99, 'Trigal'), (99, 'Moro Moro'), (99, 'Postrervalle'), (99, 'Pucará'),
(100, 'Samaipata'), (100, 'Pampa Grande'), (100, 'Mairana'), (100, 'Quirusillas'),
(101, 'Montero'), (101, 'General Saavedra'), (101, 'Mineros'), (101, 'Fernández Alonso'), (101, 'San Pedro'),
(102, 'Concepción'), (102, 'San Javier'), (102, 'San Ramón'), (102, 'San Julián'), (102, 'San Antonio de Lomerío'), (102, 'Cuatro Cañadas'),
(103, 'San Matías'),
(104, 'Comarapa'), (104, 'Saipina'),
(105, 'Puerto Suárez'), (105, 'Puerto Quijarro'), (105, 'Carmen Rivero Tórrez'),
(106, 'Ascensión de Guarayos'), (106, 'Urubichá'), (106, 'El Puente');

-- TARIJA (ID: 9)
INSERT INTO provincias (id_provincia, id_departamento, nombre) VALUES
(107, 9, 'Cercado'), (108, 9, 'Aniceto Arce'), (109, 9, 'Gran Chaco'), (110, 9, 'Avilés'), (111, 9, 'Méndez'), (112, 9, 'Burnet O''Connor');

INSERT INTO distritos (id_provincia, nombre) VALUES
(107, 'Tarija'),
(108, 'Padcaya'), (108, 'Bermejo'),
(109, 'Yacuiba'), (109, 'Caraparí'), (109, 'Villa Montes'),
(110, 'Uriondo'), (110, 'Yunchará'),
(111, 'San Lorenzo'), (111, 'El Puente'),
(112, 'Entre Ríos');
