<?php
namespace Core;

use Core\Router\Routing;
use Dotenv\Exception\InvalidPathException;
use Exception;
use PDOException;
use RuntimeException;

class Init
{
    private const REQUIRED_ENV_VARS = [
        'APP_NAME',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'JWT_SECRET',
    ];

    public function run(): void
    {
        try {
            //INICIALIZAR AS CREDENCIAIS
            $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__) . "/");
            $dotenv->load();

            //VALIDAR VARIÁVEIS DE AMBIENTE OBRIGATÓRIAS
            $this->checkRequiredEnvVars();

            //SISTEMA DE ROTAS
            $router = new Routing();
            require(dirname(__DIR__) . "/config/routes.php");
            $router->start();

        } catch (InvalidPathException $e) {
            dump($e);
        } catch (PDOException $e) {
            dump($e->getMessage());
        } catch (Exception $e) {
            dump($e);
        }
    }

    private function checkRequiredEnvVars(): void
    {
        foreach (self::REQUIRED_ENV_VARS as $var) {
            $value = $_ENV[$var] ?? getenv($var);
            if (empty($value)) {
                throw new RuntimeException(
                    "Variável de ambiente obrigatória '{$var}' não definida."
                );
            }
        }
    }
}