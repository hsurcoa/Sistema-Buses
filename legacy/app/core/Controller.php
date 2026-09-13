<?php
/*
 *  Clase Controlador Principal
 *  Carga los modelos y las vistas
 */
class Controller
{
    // Cargar modelo
    public function model($model)
    {
        // Requerir archivo de modelo
        require_once '../app/models/' . $model . '.php';
        // Instanciar modelo
        return new $model();
    }

    // Cargar vista
    public function view($view, $data = [])
    {
        // Chequear si el archivo vista existe
        if (file_exists('../app/views/' . $view . '.php')) {
            require_once '../app/views/' . $view . '.php';
        } else {
            // Vista no existe
            die('La vista no existe');
        }
    }
}
