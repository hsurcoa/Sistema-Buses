@echo off
echo Ejecutando script SQL para crear tablas del modulo de Caja...
C:\xampp\mysql\bin\mysql.exe -u root sistema_transportes < crear_tablas_caja.sql
if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo Tablas creadas exitosamente!
    echo ========================================
    echo.
) else (
    echo.
    echo ========================================
    echo ERROR al crear las tablas
    echo ========================================
    echo.
)
pause
