<?php
namespace Core\Http;

class Request
{
    public function body(?string $key = null)
    {
        $body = json_decode(file_get_contents("php://input"), true);

        // Se não for JSON válido, tenta como form-data/x-www-form-urlencoded
        if (!is_array($body)) {
            $body = $_POST ?? [];
        }

        if ($key !== null) {
            return $body[$key] ?? [];
        }

        return $body ?? [];
    }
}