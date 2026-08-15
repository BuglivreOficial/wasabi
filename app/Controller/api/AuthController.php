<?php
namespace App\Controller\Api;

use App\Controller\BaseController;
use Core\Helpers\Database;

class AuthController extends BaseController
{
    public function register(): void
    {
        //VALIDAÇÃO DOS CAMPOS RECEBIDOS
        $this->validador->validate([
            'username' => [
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

        //ARMAZERNA O CAMPOS NECESSARIO PRO CADASTRO
        $username = $this->request->body('username');
        $email = $this->request->body('email');
        $password = $this->request->body('password');

        //ARMAZENAR A INSTANCIA DO BANCO DE DADOS
        $db = Database::getInstance()->getConnection();

        //
        $sqlUsername = "SELECT 1 FROM users WHERE username = ? LIMIT 1";
        $sqlEmail = "SELECT 1 FROM users WHERE email = ? LIMIT 1";
        $sqlInsert = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)";

        //VERIFICAR SE O NOME DE USUÁRIO JÁ EXISTE
        $stmt = $db->prepare($sqlUsername);
        $stmt->execute([$username]);
        if ($stmt->fetchColumn()) {
            $this->response->json([
                'status' => false,
                'erro' => 'Nome de usuário já existe',
                'created_at' => date('d/m/Y H:i:s')
            ], 409);
        }

        //VERIFICAR SE EMAIL JÁ EXISTE
        $stmt = $db->prepare($sqlEmail);
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $this->response->json([
                'status' => false,
                'erro' => 'Email já existe',
                'created_at' => date('d/m/Y H:i:s')
            ], 409);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmtInsert = $db->prepare($sqlInsert);
        
        $stmtInsert->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password_hash' => $passwordHash
        ]);

        //SE TUDO DE CERTO RETORNAR UMA MENSAGEM DE SUCESSO
        $this->response->json([
            'status' => true,
            'message' => 'Usuário criado com sucesso!',
            'data' => [
                'token_access' => "NÃO IMPLEMENTADO AINDA"
            ],
            'created_at' => date('d/m/Y H:i:s')
        ], 200);
    }
}