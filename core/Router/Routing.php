<?php
namespace Core\Router;
;

class Routing
{
    private array $routes = [];
    public function get(string $route, array|callable $callback): void
    {
        $this->save("GET", $route, $callback);
    }
    public function post(string $route, array|callable $callback): void
    {
        $this->save("POST", $route, $callback);
    }
    public function put(string $route, array|callable $callback): void
    {
        $this->save("PUT", $route, $callback);
    }
    public function delete(string $route, array|callable $callback): void
    {
        $this->save("DELETE", $route, $callback);
    }
    protected function save(string $method, string $route, array|callable $callback): void
    {
        if (isset($this->routes[$route][$method])) {
            throw new RoutingException("Rota duplicada no arquivos de rotas");
        }

        if (!class_exists($callback[0])) {
            throw new RoutingException("Class não existe!");
        }
        if (!method_exists($callback[0], $callback[1])) {
            throw new RoutingException("Class existe, mais metodo não definido!");
        }

        $this->routes[$route][$method] = [
            "controller" => $callback[0],
            "method" => $callback[1]
        ];
    }
    public function start(): void
    {
        $uri = '/' . trim($_SERVER['REQUEST_URI'], '/');
        $method = $_SERVER['REQUEST_METHOD'] ?? "GET";

        if (!isset($this->routes[$uri])) {
            throw new RoutingException("Rota não existe!");
        }
        if (!isset($this->routes[$uri][$method])) {
            throw new RoutingException("Rota existe, mais o metodo de requisição não e aceito pelo recurso de destino");
        }

        $controller = $this->routes[$uri][$method]['controller'];
        $function = $this->routes[$uri][$method]['method'];

        $instace = new $controller();
        
        $instace->$function();
    }
}