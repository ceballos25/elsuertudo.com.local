<?php

namespace App\Core;

abstract class Controller
{
    protected function json(array $data, int $code = 200): void
    {
        Response::json($data, $code);
    }
}
