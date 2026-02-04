<?php

namespace App\Core;

global $auth_status;

class Controller
{
    public function view($viewName, $data = []): void
    {
        extract($data);
        $viewName = str_replace('.', '/', $viewName);
        require __DIR__ . "/../Views/{$viewName}.view.php";
    }

    public function json($data)
    {
        echo json_encode($data);
        exit;
    }
}