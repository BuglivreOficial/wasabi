<?php

require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

// INICIALIZAR AS CREDENCIAIS
$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1) . "/");
$dotenv->load();

echo "Iniciando o worker de envio de e-mails de redefinição de senha...\n";

$mailer = new \Core\Helpers\Mailer(
    host: $_ENV['MAIL_HOST'],
    username: $_ENV['MAIL_USERNAME'],
    password: $_ENV['MAIL_PASSWORD'],
    port: (int) $_ENV['MAIL_PORT'],
    encryption: $_ENV['MAIL_ENCRYPTION']
);

$cache = new \Core\Helpers\RedisCache(
    host: $_ENV['REDIS_HOST'] ?? '127.0.0.1',
    port: (int) ($_ENV['REDIS_PORT'] ?? 6379),
    password: $_ENV['REDIS_PASSWORD'] ?? null,
    database: (int) ($_ENV['REDIS_DB'] ?? 0),
    prefix: 'auth'
);

// Conexão com o banco criada uma única vez, fora do loop
$db = \Core\Helpers\Database::getInstance()->getConnection();
$stmtBuscaUsuario = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');

const INTERVALO_ESPERA_SEGUNDOS = 5; // tempo máx. de espera por item antes de checar novamente (mantém o loop vivo)

while (true) {
    $resultado = $cache->blockingPopLeft('fila:reset-senha', timeoutSeconds: INTERVALO_ESPERA_SEGUNDOS);

    if ($resultado === null) {
        // Timeout sem itens na fila — volta a aguardar sem consumir CPU
        continue;
    }

    $item = $resultado['value'];

    try {
        $stmtBuscaUsuario->execute([$item['email']]);
        $usuario = $stmtBuscaUsuario->fetch();

        // E-mail não encontrado: silêncio proposital (não revela existência)
        if (!$usuario) {
            continue;
        }

        // Invalida qualquer token de reset anterior desse usuário
        $tokenAntigo = $cache->get("reset-user:{$usuario['id']}");
        if ($tokenAntigo !== null) {
            $cache->delete("reset-token:{$tokenAntigo}");
        }

        // Grava o novo token e a referência reversa (usuário -> token)
        $cache->set("reset-token:{$item['token']}", $usuario['id'], ttl: 3600);
        $cache->set("reset-user:{$usuario['id']}", $item['token'], ttl: 3600);

        $mailer->clearAddresses();
        $mailer
            ->setFrom('BugLivreOficial@gmail.com', 'Bug Livre Oficial')
            ->addAddress($item['email'])
            ->setSubject('Redefinição de senha')
            ->setBody("Clique no link para redefinir: $_ENV[APP_URL]/auth/reset-password?token={$item['token']}");

        if ($mailer->send()) {
            echo "[" . date('H:i:s') . "] E-mail enviado para: {$item['email']}\n";
        } else {
            echo "[" . date('H:i:s') . "] Falha ao enviar para {$item['email']}: "
                . implode(', ', $mailer->getErrors()) . "\n";
        }
    } catch (\Throwable $e) {
        // Nunca deixa uma falha pontual derrubar o worker inteiro
        echo "[" . date('H:i:s') . "] Erro ao processar item da fila: {$e->getMessage()}\n";
    }
}