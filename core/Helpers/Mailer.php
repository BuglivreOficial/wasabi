<?php
namespace Core\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Classe Mailer
 *
 * Encapsula o PHPMailer para facilitar o envio de e-mails via SMTP,
 * com suporte a HTML, anexos, CC/BCC e tratamento de erros.
 *
 * Requer instalação via Composer:
 *   composer require phpmailer/phpmailer
 */
class Mailer
{
    private PHPMailer $mail;
    private array $errors = [];

    public function __construct(
        string $host,
        string $username,
        string $password,
        int $port = 587,
        string $encryption = PHPMailer::ENCRYPTION_STARTTLS
    ) {
        $this->mail = new PHPMailer(true);

        try {
            // Configurações do servidor SMTP
            $this->mail->isSMTP();
            $this->mail->Host       = $host;
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $username;
            $this->mail->Password   = $password;
            $this->mail->SMTPSecure = $encryption; // 'tls' ou 'ssl'
            $this->mail->Port       = $port;

            // Codificação padrão
            $this->mail->CharSet = PHPMailer::CHARSET_UTF8;
            $this->mail->isHTML(true);

        } catch (Exception $e) {
            $this->errors[] = "Erro na configuração do Mailer: {$this->mail->ErrorInfo}";
        }
    }

    /**
     * Define o remetente do e-mail
     */
    public function setFrom(string $email, string $name = ''): self
    {
        try {
            $this->mail->setFrom($email, $name);
        } catch (Exception $e) {
            $this->errors[] = "Erro ao definir remetente: {$this->mail->ErrorInfo}";
        }
        return $this;
    }

    /**
     * Adiciona um destinatário
     */
    public function addAddress(string $email, string $name = ''): self
    {
        try {
            $this->mail->addAddress($email, $name);
        } catch (Exception $e) {
            $this->errors[] = "Erro ao adicionar destinatário: {$this->mail->ErrorInfo}";
        }
        return $this;
    }

    /**
     * Adiciona um destinatário em cópia (CC)
     */
    public function addCC(string $email, string $name = ''): self
    {
        try {
            $this->mail->addCC($email, $name);
        } catch (Exception $e) {
            $this->errors[] = "Erro ao adicionar CC: {$this->mail->ErrorInfo}";
        }
        return $this;
    }

    /**
     * Adiciona um destinatário em cópia oculta (BCC)
     */
    public function addBCC(string $email, string $name = ''): self
    {
        try {
            $this->mail->addBCC($email, $name);
        } catch (Exception $e) {
            $this->errors[] = "Erro ao adicionar BCC: {$this->mail->ErrorInfo}";
        }
        return $this;
    }

    /**
     * Define um endereço para resposta (Reply-To)
     */
    public function addReplyTo(string $email, string $name = ''): self
    {
        try {
            $this->mail->addReplyTo($email, $name);
        } catch (Exception $e) {
            $this->errors[] = "Erro ao adicionar Reply-To: {$this->mail->ErrorInfo}";
        }
        return $this;
    }

    /**
     * Anexa um arquivo ao e-mail
     */
    public function addAttachment(string $path, string $name = ''): self
    {
        try {
            if (!file_exists($path)) {
                $this->errors[] = "Arquivo de anexo não encontrado: {$path}";
                return $this;
            }
            $this->mail->addAttachment($path, $name);
        } catch (Exception $e) {
            $this->errors[] = "Erro ao adicionar anexo: {$this->mail->ErrorInfo}";
        }
        return $this;
    }

    /**
     * Define o assunto do e-mail
     */
    public function setSubject(string $subject): self
    {
        $this->mail->Subject = $subject;
        return $this;
    }

    /**
     * Define o corpo do e-mail (HTML)
     *
     * @param string $body Corpo em HTML
     * @param string $altBody Versão em texto puro (fallback)
     */
    public function setBody(string $body, string $altBody = ''): self
    {
        $this->mail->Body = $body;
        $this->mail->AltBody = $altBody !== '' ? $altBody : strip_tags($body);
        return $this;
    }

    /**
     * Define se o corpo é HTML ou texto puro
     */
    public function isHTML(bool $isHtml = true): self
    {
        $this->mail->isHTML($isHtml);
        return $this;
    }

    /**
     * Habilita modo de depuração SMTP (para debug)
     */
    public function setDebug(int $level = SMTP::DEBUG_SERVER): self
    {
        $this->mail->SMTPDebug = $level;
        return $this;
    }

    /**
     * Envia o e-mail
     *
     * @return bool true se enviado com sucesso, false caso contrário
     */
    public function send(): bool
    {
        try {
            $result = $this->mail->send();
            return $result;
        } catch (Exception $e) {
            $this->errors[] = "Erro ao enviar e-mail: {$this->mail->ErrorInfo}";
            return false;
        }
    }

    /**
     * Retorna os erros ocorridos durante o processo
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Verifica se houve algum erro
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Limpa todos os destinatários (para reutilizar a instância)
     */
    public function clearAddresses(): self
    {
        $this->mail->clearAddresses();
        $this->mail->clearCCs();
        $this->mail->clearBCCs();
        $this->mail->clearAttachments();
        return $this;
    }

    /**
     * Acesso direto à instância do PHPMailer, caso precise
     * de alguma configuração avançada não coberta pela classe
     */
    public function getPHPMailerInstance(): PHPMailer
    {
        return $this->mail;
    }
}