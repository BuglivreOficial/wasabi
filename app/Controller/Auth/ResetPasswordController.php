<?php
namespace App\Controller\Auth;

class ResetPasswordController
{
    private $response;
    private $request;
    private $validador;
    private $mailer;
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
    }
    public function showResetForm(): void
    {
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

        //ARMAZENAR A INSTANCIA DO BANCO DE DADOS
        $db = Database::getInstance()->getConnection();

        //
        $sqlEmail = "SELECT username FROM users WHERE email = ? LIMIT 1";

        //VERIFICAR SE EMAIL JÁ EXISTE
        $stmt = $db->prepare($sqlEmail);
        $stmt->execute([$email]);
        if ($username = $stmt->fetchColumn()) {
            // Aqui você pode gerar um token de redefinição de senha e enviar o e-mail
            $resetToken = bin2hex(random_bytes(16)); // Exemplo de token
            $resetLink = "https://seusite.com/reset-password?token={$resetToken}";

            // Enviar e-mail com o link de redefinição
            $this->mailer
                ->setFrom('buglivreoficial@gmail.com', 'Bug Livre Oficial')
                ->addAddress($email, $username)
                ->setSubject('Redefinição de Senha')
                ->setBody(
                    body: "<p>Olá, {$username}!</p><p>Clique no link abaixo para redefinir sua senha:</p><p><a href='{$resetLink}'>Redefinir Senha</a></p>",
                    altBody: "Olá, {$username}! Clique no link abaixo para redefinir sua senha: {$resetLink}"
                )->send();
        }


        //SE TUDO DE CERTO RETORNAR UMA MENSAGEM DE SUCESSO
        $this->response->json([
            'status' => true,
            'message' => 'Se o e-mail estiver registrado, você receberá um link para redefinir sua senha.',
            'code' => 'AUTH_SUCCESS_' . bin2hex(random_bytes(3)),
            'created_at' => date('d/m/Y H:i:s')
        ], 200);
    }
}