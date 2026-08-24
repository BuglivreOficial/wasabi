<?php
namespace Core\Http;

class Response
{
    public function json(array $data, int $status_code = 200, array $headers = []): void
    {
        header('Content-type: application/json');

        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }

        http_response_code($status_code);
        echo json_encode($data);
        exit;
    }
    public function view(string $view, array $data = [], int $status_code = 200): void
    {
        http_response_code($status_code);
        foreach ($data as $key => $value) {
            $$key = $value;
        }
        require dirname(__DIR__, 2) . '/layout/' . $view . '.php';
        exit;
    }
    public function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}