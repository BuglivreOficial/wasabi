<?php
namespace Core\Http;

class Response
{
    public function json(array $data, int $status_code = 200): void
    {
        header('Content-type: application/json');
        http_response_code($status_code);
        echo json_encode($data);
        exit;
    }
    public function view(string $view): void
    {
       require dirname(__DIR__, 2) . '/layout/' . $view . '.html';
    }
}