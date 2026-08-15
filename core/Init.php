<?php
namespace Core;

use Core\Router\Routing;
use Dotenv\Exception\InvalidPathException;
use Exception;
use PDOException;

class Init
{
    public function run(): void
    {
        try {
            //INICIALIZAR AS CREDENCIAIS
            $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__) . "/");
            $dotenv->load();

            //SISTEMA DE ROTAS
            $router = new Routing();
            require(dirname(__DIR__) . "/config/routes.php");
            $router->start();

        } catch (Exception $e) {
            dump($e);
        } catch(InvalidPathException $e) {
            dump($e);
        } catch(PDOException $e) {
            dump($e->getMessage());
        }
    }
}