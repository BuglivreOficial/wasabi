<?php
namespace Core\Http;

class Response
{
    public function json(array $data, int $status_code = 200): void
    {
        header('Content-type: application/json');
        http_response_code($status_code);
        echo json_encode($data);
    }
}