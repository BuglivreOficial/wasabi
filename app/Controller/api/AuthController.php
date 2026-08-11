<?php
namespace App\Controller\Api;

use App\Controller\BaseController;

class AuthController extends BaseController
{
    public function register(): void { 
        //VALIDAÇÃO DOS CAMPOS RECEBIDOS
        $this->validador->validate([
            'name' => [
                'rules' => 'required|min:3|max:30',
                'name' => 'nome de usuário'
            ],
            'email' => 'required|email',
            'password' => [
                'rules' => 'required|min:6|max:64',
                'name' => 'senha'
            ]
        ], $this->request->body());
        //SE A VALIDAÇÃO FALHAR MANDAR RESPOSTA DOS ERROS
        if ($this->validador->fails()) {
            $this->response->json([
                'status' => false,
                'erros' => $this->validador->errors(),
                'created_at' => date('d/m/Y H:i:s')
            ]);
        }
        
        //
    }
}