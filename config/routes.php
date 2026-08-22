<?php

use App\Controller\Api\AuthController;
use App\Controller\Auth\ResetPasswordController;
use Core\Router\Routing;

/** @var Routing $router */



//ROTAS DE AUTENTICAÇÃO
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/register', [AuthController::class, 'register']);

$router->post('/auth/forgot-password', [ResetPasswordController::class, 'forgotPassword']);
$router->get('/auth/reset-password', [ResetPasswordController::class, 'showResetForm']);