<?php

use App\Controller\Api\AuthController;
use App\Controller\Api\TesteController;
use Core\Router\Routing;

/** @var Routing $router */



//ROTAS DE AUTENTICAÇÃO
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/register', [AuthController::class, 'register']);