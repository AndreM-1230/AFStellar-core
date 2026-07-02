<?php

namespace App\Core;

class Controller
{
    public function view($viewName, $data = [])
    {
        $viewName = str_replace('.', '/', $viewName);
        echo SimpleBlade::render(__DIR__ . "/../Views/{$viewName}.view.php", $data);
    }

    public function json($data)
    {
        echo json_encode($data);
        exit;
    }
}