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
        //Se o callback for uma função anônima, apenas salva a função
        if(is_callable($callback)) {
            $this->routes[$route][$method] = [
                "controller" => $callback,
                "method" => null
            ];
            return;
        }
        //Se o callback for um array, verifica se a classe e o método existem
        if (!class_exists($callback[0])) {
            throw new RoutingException("Class não existe!");
        }
        if (!method_exists($callback[0], $callback[1])) {
            throw new RoutingException("Class existe, mais metodo não definido!");
        }

        //Salva a rota com o método e a classe
        $this->routes[$route][$method] = [
            "controller" => $callback[0],
            "method" => $callback[1]
        ];
    }
    public function start(): void
    {
        $uri = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $method = $_SERVER['REQUEST_METHOD'] ?? "GET";

        if (!isset($this->routes[$uri])) {
            throw new RoutingException("Rota não existe!");
        }
        if (!isset($this->routes[$uri][$method])) {
            throw new RoutingException("Rota existe, mais o metodo de requisição não e aceito pelo recurso de destino");
        }

        if (is_callable($this->routes[$uri][$method]['controller'])) {
            $callback = $this->routes[$uri][$method]['controller'];
            $callback();
            return;
        }
        $controller = $this->routes[$uri][$method]['controller'];
        $function = $this->routes[$uri][$method]['method'];

        $instace = new $controller();
        
        $instace->$function();
    }
}