<?php
namespace App\Controller\Api;

use App\Controller\BaseController;

class TesteController extends BaseController
{
    public function get(): void
    {
        echo "Rota GET!";
    }
    public function post(): void
    {
        $erros = $this->validador->validate([
            'email' => 'required|email',
            'password' => [
                'rules' => 'required|min:6|max:64',
                'name' => 'senha'
            ]
        ], $this->request->body());

        dump($erros);
    }
}