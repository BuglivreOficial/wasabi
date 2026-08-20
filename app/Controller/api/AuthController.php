<?php
namespace App\Controller\Api;

use App\Controller\BaseController;
use Core\Helpers\Database;
use Core\Helpers\JwtService;

class AuthController extends BaseController
{
    public function login()
    {
        //VALIDAÇÃO DOS CAMPOS RECEBIDOS
        $this->validador->validate([
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
            ], 422);
        }

        //ARMAZERNA O CAMPOS NECESSARIO
        $email = $this->request->body('email');
        $password = $this->request->body('password');

        //ARMAZENAR A INSTANCIA DO BANCO DE DADOS
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT id, username, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->response->json([
                'status' => false,
                'erro' => 'Credenciais inválidas',
                'code' => 'AUTH_ERROR_' . bin2hex(random_bytes(3)),
                'created_at' => date('d/m/Y H:i:s')
            ], 401);
            return;
        }

        $jwtService = new JwtService();
        $token = $jwtService->generate([
            'sub' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user',
            'status' => $user['status'] ?? 'active'
        ]);

        $this->response->json([
            'status' => true,
            'message' => 'Login realizado com sucesso!',
            'data' => [
                'token_access' => $token,
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'user',
                'status' => $user['status'] ?? 'active',
            ],
            'code' => 'AUTH_SUCCESS_' . bin2hex(random_bytes(3)),
            'created_at' => date('d/m/Y H:i:s')
        ], 200);
    }
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
            ], 422);
        }

        //ARMAZERNA O CAMPOS NECESSARIO PRO CADASTRO
        $username = $this->request->body('username');
        $email = $this->request->body('email');
        $password = $this->request->body('password');

        //ARMAZENAR A INSTANCIA DO BANCO DE DADOS
        $db = Database::getInstance()->getConnection();

        //
        $sqlEmail = "SELECT 1 FROM users WHERE email = ? LIMIT 1";
        $sqlInsert = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)";

        $sqlUsername = "SELECT 1 FROM users WHERE username = ? LIMIT 1";
        //VERIFICAR SE O NOME DE USUÁRIO JÁ EXISTE
        $stmt = $db->prepare($sqlUsername);
        $stmt->execute([$username]);
        if ($stmt->fetchColumn()) {
            $this->response->json([
                'status' => false,
                'erro' => 'Nome de usuário já existe',
                'code' => 'AUTH_ERROR_' . bin2hex(random_bytes(3)),
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
                'code' => 'AUTH_ERROR_' . bin2hex(random_bytes(3)),
                'created_at' => date('d/m/Y H:i:s')
            ], 409);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmtInsert = $db->prepare($sqlInsert);
        try {
            $stmtInsert->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $passwordHash
            ]);
        } catch (\PDOException $e) {
            // FALLBACK CONTRA CONCORRENCIA (RACE CONDITION) ENTRE SELECT E INSERT
            if ($e->getCode() === '23000') {
                $this->response->json([
                    'status' => false,
                    'erro' => 'Usuário ou email já existe',
                    'code' => 'AUTH_ERROR_' . bin2hex(random_bytes(3)),
                    'created_at' => date('d/m/Y H:i:s')
                ], 409);
                return;
            }
            throw $e; // outros erros de banco sobem normalmente
        }

        $userId = $db->lastInsertId();

        $jwtService = new JwtService();
        $token = $jwtService->generate([
            'sub' => $userId,
            'username' => $username,
            'email' => $email
        ]);

        //SE TUDO DE CERTO RETORNAR UMA MENSAGEM DE SUCESSO
        $this->response->json([
            'status' => true,
            'message' => 'Usuário criado com sucesso!',
            'data' => [
                'token_access' => $token,
                'username' => $username,
                'email' => $email,
                'role' => 'user',
                'status' => 'active',
            ],
            'code' => 'AUTH_SUCCESS_' . bin2hex(random_bytes(3)),
            'created_at' => date('d/m/Y H:i:s')
        ], 200);
    }
}