<?php

namespace App\Core;

class Controller
{
    public function view($viewName, $data = [])
    {
        extract($data);
        $viewName = str_replace('.', '/', $viewName);
        require __DIR__ . "/../Views/{$viewName}.php";
    }

    public function json($data)
    {
        echo json_encode($data);
        exit;
    }
}