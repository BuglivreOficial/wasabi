<?php
namespace App\Controller\Auth;

use Core\Helpers\RedisCache;
class ResetPasswordController
{
    private $response;
    private $request;
    private $validador;
    private $mailer;
    private $cache;
    public function __construct()
    {
        $this->response = new \Core\Http\Response();
        $this->request = new \Core\Http\Request();
        $this->validador = new \Core\Helpers\Validation();
        $this->mailer = new \Core\Helpers\Mailer(
            host: 'smtp.gmail.com',
            username: 'buglivreoficial@gmail.com',
            password: 'ubammijknnetyudq', // use senha de app, não a senha normal
            port: 587,
            encryption: 'tls'
        );
        $this->cache = new RedisCache(
            host: '127.0.0.1',
            port: 6379,
            password: null,       // defina se seu Redis exigir autenticação
            database: 0,
            prefix: 'auth'    // opcional: prefixo para todas as chaves
        );
    }
    public function viewForm(): void
    {
        $token = $_GET['token'] ?? '';
        $usuarioId = $this->cache->get("reset-token:{$token}");

        if ($usuarioId === null) {
            $this->response->view('/error/404', [
                'url' => $_SERVER['REQUEST_URI'] ?? 'URL não fornecida'
            ], 404);
        }

        // invalida o token depois de usado
        //$this->cache->delete("reset-token:{$token}");
        //$this->cache->delete("reset-user:{$usuarioId}");

        $this->response->view('app/reset-password');
    }

    public function forgotPassword(): void
    {
        //VALIDAÇÃO DOS CAMPOS RECEBIDOS
        $this->validador->validate([
            'email' => 'required|email'
        ], $this->request->body());

        //SE A VALIDAÇÃO FALHAR MANDAR RESPOSTA DOS ERROS
        if ($this->validador->fails()) {
            $this->response->json([
                'status' => false,
                'erros' => $this->validador->errors(),
                'created_at' => date('d/m/Y H:i:s')
            ], 422);
        }

        //ARMAZERNA O CAMPOS NECESSARIO PRA ESSE PROCESSO
        $email = $this->request->body('email');

        $rate = $this->cache->rateLimit(
            identificador: 'reset-senha:' . $email,
            maxTentativas: 5,
            janelaSegundos: 120
        );

        if (!$rate['permitido']) {
            $this->response->json([
                'status' => false,
                'message' => 'Muitas tentativas. Tente novamente em ' . $rate['ttl'] . ' segundos.',
                'retry_after' => $rate['ttl'],
                'created_at' => date('d/m/Y H:i:s')
            ], 429, [
                'Retry-After' => $rate['ttl']
            ]);
            return;
        }

        // Coloca o e-mail na fila com os dados necessários para o envio
        $dados = [
            'email' => $email,
            'token' => bin2hex(random_bytes(32)), // token de redefinição
            'criado_em' => time(),
        ];

        $this->cache->pushRight('fila:reset-senha', $dados);


        //SE TUDO DE CERTO RETORNAR UMA MENSAGEM DE SUCESSO
        $this->response->json([
            'status' => true,
            'message' => 'Se o e-mail estiver registrado, você receberá um link para redefinir sua senha.',
            'code' => 'AUTH_SUCCESS_' . bin2hex(random_bytes(3)),
            'created_at' => date('d/m/Y H:i:s')
        ], 200);
    }
}