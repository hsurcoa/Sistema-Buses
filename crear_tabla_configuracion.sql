CREATE TABLE IF NOT EXISTS configuracion_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Limpiar si ya existen para reiniciar valores por defecto o insertar si no existen
INSERT IGNORE INTO configuracion_sistema (clave, valor) VALUES 
('empresa_nombre', 'Valhalla'),
('empresa_slogan', 'EMPRESA DE TRANSPORTE'),
('empresa_logo', '');
