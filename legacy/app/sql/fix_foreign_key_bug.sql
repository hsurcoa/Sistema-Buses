
-- 1. Eliminar la restricción de clave foránea incorrecta (que apunta a la tabla 'buses' obsoleta/vacía)
ALTER TABLE viajes DROP FOREIGN KEY viajes_ibfk_2;

-- 2. Agregar la nueva restricción correcta (que apunta a la tabla 'vehiculos' que usa el sistema actual)
ALTER TABLE viajes ADD CONSTRAINT viajes_ibfk_2 FOREIGN KEY (bus_id) REFERENCES vehiculos(id);

-- 3. Asegurar que el índice existe (por si acaso mysql lo borró al borrar la FK, aunque usualmente queda)
-- CREATE INDEX IDX_VIAJES_BUS_ID ON viajes(bus_id); 
