<?php
namespace Core\Helpers;
use Redis;
/**
 * Classe RedisCache
 *
 * Encapsula a extensão phpredis para facilitar operações comuns:
 * cache de strings, hashes, listas, sets, expiração e locks simples.
 *
 * Requer a extensão phpredis instalada:
 *   sudo apt-get install php-redis
 *   ou via pecl: pecl install redis
 *
 * Verifique com: php -m | grep redis
 */
class RedisCache
{
    private Redis $redis;
    private array $errors = [];
    private bool $connected = false;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379,
        ?string $password = null,
        int $database = 0,
        float $timeout = 2.5,
        string $prefix = ''
    ) {
        $this->redis = new Redis();

        try {
            $this->connected = $this->redis->connect($host, $port, $timeout);

            if (!$this->connected) {
                $this->errors[] = "Não foi possível conectar ao Redis em {$host}:{$port}";
                return;
            }

            if ($password !== null) {
                $this->redis->auth($password);
            }

            if ($database !== 0) {
                $this->redis->select($database);
            }

            if ($prefix !== '') {
                $this->redis->setOption(Redis::OPT_PREFIX, $prefix . ':');
            }

            // Serializador para permitir armazenar arrays/objetos automaticamente
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

        } catch (\RedisException $e) {
            $this->errors[] = "Erro na conexão com Redis: {$e->getMessage()}";
            $this->connected = false;
        }
    }

    /**
     * Verifica se a conexão está ativa
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    // ==========================================================
    // STRINGS / CACHE SIMPLES
    // ==========================================================

    /**
     * Armazena um valor, opcionalmente com TTL (segundos)
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            if ($ttl !== null) {
                return $this->redis->setex($key, $ttl, $value);
            }
            return $this->redis->set($key, $value);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao definir chave '{$key}': {$e->getMessage()}";
            return false;
        }
    }

    /**
     * Recupera um valor. Retorna $default se não existir.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $value = $this->redis->get($key);
            return $value === false ? $default : $value;
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao obter chave '{$key}': {$e->getMessage()}";
            return $default;
        }
    }

    /**
     * Cache "remember": retorna do cache se existir, senão executa o
     * callback, armazena o resultado e retorna.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Remove uma ou mais chaves
     */
    public function delete(string ...$keys): int
    {
        try {
            return $this->redis->del($keys);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao deletar chave(s): {$e->getMessage()}";
            return 0;
        }
    }

    /**
     * Verifica se uma chave existe
     */
    public function has(string $key): bool
    {
        try {
            return $this->redis->exists($key) > 0;
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao verificar chave '{$key}': {$e->getMessage()}";
            return false;
        }
    }

    /**
     * Define o tempo de expiração (TTL) de uma chave existente
     */
    public function expire(string $key, int $seconds): bool
    {
        try {
            return $this->redis->expire($key, $seconds);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao definir expiração de '{$key}': {$e->getMessage()}";
            return false;
        }
    }

    /**
     * Retorna o TTL restante de uma chave (em segundos). -1 = sem expiração, -2 = não existe.
     */
    public function ttl(string $key): int
    {
        try {
            return $this->redis->ttl($key);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao obter TTL de '{$key}': {$e->getMessage()}";
            return -2;
        }
    }

    /**
     * Incrementa um valor numérico
     */
    public function increment(string $key, int $by = 1): int|false
    {
        try {
            return $by === 1 ? $this->redis->incr($key) : $this->redis->incrBy($key, $by);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao incrementar '{$key}': {$e->getMessage()}";
            return false;
        }
    }

    /**
     * Decrementa um valor numérico
     */
    public function decrement(string $key, int $by = 1): int|false
    {
        try {
            return $by === 1 ? $this->redis->decr($key) : $this->redis->decrBy($key, $by);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao decrementar '{$key}': {$e->getMessage()}";
            return false;
        }
    }

    // ==========================================================
    // RATE LIMITING
    // ==========================================================

    /**
     * Aplica rate limit por janela fixa a uma chave (ex: e-mail, IP, user ID).
     *
     * Uso típico:
     *   $r = $cache->rateLimit('reset-senha:' . strtolower($email), maxTentativas: 5, janelaSegundos: 3600);
     *   if (!$r['permitido']) { ... 429 ... }
     *
     * @param string $identificador Identifica quem está sendo limitado (e-mail, IP, etc).
     *                              A chave final é prefixada com "ratelimit:".
     * @param int $maxTentativas Número máximo de requisições permitidas na janela.
     * @param int $janelaSegundos Duração da janela em segundos (ex: 3600 = 1 hora).
     *
     * @return array{
     *   permitido: bool,
     *   tentativas: int,
     *   restantes: int,
     *   ttl: int
     * }
     */
    public function rateLimit(string $identificador, int $maxTentativas, int $janelaSegundos): array
    {
        $key = 'ratelimit:' . md5(strtolower($identificador));

        $tentativas = $this->increment($key);

        // Se é a primeira requisição dessa janela, define a expiração.
        // (increment() retorna 1 apenas na primeira chamada após a chave não existir)
        if ($tentativas === 1) {
            $this->expire($key, $janelaSegundos);
        }

        $ttl = $this->ttl($key);
        // Se por algum motivo a chave não tiver TTL (ex: corrida rara), força um.
        if ($ttl < 0) {
            $this->expire($key, $janelaSegundos);
            $ttl = $janelaSegundos;
        }

        return [
            'permitido'  => $tentativas <= $maxTentativas,
            'tentativas' => $tentativas,
            'restantes'  => max(0, $maxTentativas - $tentativas),
            'ttl'        => $ttl,
        ];
    }

    /**
     * Reseta o contador de rate limit de um identificador (ex: após login bem-sucedido).
     */
    public function resetRateLimit(string $identificador): bool
    {
        $key = 'ratelimit:' . md5(strtolower($identificador));
        return $this->delete($key) > 0;
    }

    // ==========================================================
    // HASHES
    // ==========================================================

    public function hSet(string $key, string $field, mixed $value): bool
    {
        try {
            return (bool) $this->redis->hSet($key, $field, $value);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao definir hash '{$key}.{$field}': {$e->getMessage()}";
            return false;
        }
    }

    public function hGet(string $key, string $field, mixed $default = null): mixed
    {
        try {
            $value = $this->redis->hGet($key, $field);
            return $value === false ? $default : $value;
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao obter hash '{$key}.{$field}': {$e->getMessage()}";
            return $default;
        }
    }

    public function hGetAll(string $key): array
    {
        try {
            return $this->redis->hGetAll($key) ?: [];
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao obter hash completo '{$key}': {$e->getMessage()}";
            return [];
        }
    }

    public function hDelete(string $key, string ...$fields): int
    {
        try {
            return $this->redis->hDel($key, ...$fields);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao deletar campo(s) do hash '{$key}': {$e->getMessage()}";
            return 0;
        }
    }

    // ==========================================================
    // LISTAS (filas)
    // ==========================================================

    public function pushLeft(string $key, mixed ...$values): int|false
    {
        try {
            return $this->redis->lPush($key, ...$values);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao inserir na lista '{$key}': {$e->getMessage()}";
            return false;
        }
    }

    public function pushRight(string $key, mixed ...$values): int|false
    {
        try {
            return $this->redis->rPush($key, ...$values);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao inserir na lista '{$key}': {$e->getMessage()}";
            return false;
        }
    }

    public function popLeft(string $key): mixed
    {
        try {
            $value = $this->redis->lPop($key);
            return $value === false ? null : $value;
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao remover da lista '{$key}': {$e->getMessage()}";
            return null;
        }
    }

    public function popRight(string $key): mixed
    {
        try {
            $value = $this->redis->rPop($key);
            return $value === false ? null : $value;
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao remover da lista '{$key}': {$e->getMessage()}";
            return null;
        }
    }

    /**
     * Remove e retorna o primeiro item de uma (ou mais) lista(s), aguardando
     * até $timeoutSeconds caso esteja(m) vazia(s), em vez de retornar
     * imediatamente. Elimina a necessidade de polling com usleep() em loops
     * de worker.
     *
     * @param string|array $keys Uma chave ou array de chaves (verifica em ordem)
     * @param int $timeoutSeconds Tempo máximo de espera. 0 = espera indefinidamente.
     *
     * @return array{key: string, value: mixed}|null Null se o timeout expirar sem itens.
     */
    public function blockingPopLeft(string|array $keys, int $timeoutSeconds = 5): ?array
    {
        try {
            $result = $this->redis->blPop((array) $keys, $timeoutSeconds);

            // phpredis retorna [] (array vazio) quando o timeout expira sem itens
            if (empty($result)) {
                return null;
            }

            return [
                'key'   => $result[0],
                'value' => $result[1],
            ];
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao aguardar item em '" . implode(',', (array) $keys) . "': {$e->getMessage()}";
            return null;
        }
    }

    /**
     * Igual a blockingPopLeft(), mas remove do final da lista (rPop) em vez do início.
     */
    public function blockingPopRight(string|array $keys, int $timeoutSeconds = 5): ?array
    {
        try {
            $result = $this->redis->brPop((array) $keys, $timeoutSeconds);

            if (empty($result)) {
                return null;
            }

            return [
                'key'   => $result[0],
                'value' => $result[1],
            ];
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao aguardar item em '" . implode(',', (array) $keys) . "': {$e->getMessage()}";
            return null;
        }
    }

    public function listLength(string $key): int
    {
        try {
            return $this->redis->lLen($key);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao obter tamanho da lista '{$key}': {$e->getMessage()}";
            return 0;
        }
    }

    // ==========================================================
    // SETS
    // ==========================================================

    public function setAdd(string $key, mixed ...$members): int|false
    {
        try {
            return $this->redis->sAdd($key, ...$members);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao adicionar ao set '{$key}': {$e->getMessage()}";
            return false;
        }
    }

    public function setMembers(string $key): array
    {
        try {
            return $this->redis->sMembers($key) ?: [];
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao obter membros do set '{$key}': {$e->getMessage()}";
            return [];
        }
    }

    public function setIsMember(string $key, mixed $member): bool
    {
        try {
            return (bool) $this->redis->sIsMember($key, $member);
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao verificar membro do set '{$key}': {$e->getMessage()}";
            return false;
        }
    }

    // ==========================================================
    // LOCK SIMPLES (útil para evitar processamento concorrente)
    // ==========================================================

    /**
     * Tenta adquirir um lock. Retorna true se conseguiu.
     */
    public function lock(string $key, int $ttlSeconds = 10): bool
    {
        try {
            // NX = só define se não existir; EX = expiração em segundos
            return (bool) $this->redis->set(
                "lock:{$key}",
                '1',
                ['NX', 'EX' => $ttlSeconds]
            );
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao adquirir lock '{$key}': {$e->getMessage()}";
            return false;
        }
    }

    /**
     * Libera um lock
     */
    public function unlock(string $key): bool
    {
        return $this->delete("lock:{$key}") > 0;
    }

    // ==========================================================
    // UTILITÁRIOS
    // ==========================================================

    /**
     * Limpa todo o banco de dados atual (use com cautela)
     */
    public function flush(): bool
    {
        try {
            return $this->redis->flushDB();
        } catch (\RedisException $e) {
            $this->errors[] = "Erro ao limpar banco: {$e->getMessage()}";
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
     * Acesso direto à instância do Redis, caso precise
     * de alguma operação avançada não coberta pela classe
     */
    public function getRedisInstance(): Redis
    {
        return $this->redis;
    }

    public function __destruct()
    {
        if ($this->connected) {
            try {
                $this->redis->close();
            } catch (\RedisException) {
                // conexão já pode ter sido encerrada
            }
        }
    }
}