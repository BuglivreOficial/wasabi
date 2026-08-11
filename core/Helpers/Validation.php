<?php
namespace Core\Helpers;

class Validation
{
    protected array $errors = [];

    public function validate(array $rules, array $body): array
    {
        $this->errors = [];

        foreach ($rules as $field => $config) {
            // Normaliza o formato: string simples OU array ['rules' => ..., 'name' => ...]
            if (is_array($config)) {
                $ruleString = $config['rules'] ?? '';
                $fieldName  = $config['name'] ?? $field;
            } else {
                $ruleString = $config;
                $fieldName  = $field;
            }

            $value = $body[$field] ?? null;
            $rulesList = explode('|', $ruleString);

            foreach ($rulesList as $rule) {
                $this->applyRule($field, $fieldName, $value, $rule, $body);
            }
        }

        return $this->errors;
    }

    protected function applyRule(string $field, string $fieldName, $value, string $rule, array $body): void
    {
        // Se já tem erro nesse campo, evita empilhar mensagens redundantes (opcional)
        // if (isset($this->errors[$field])) return;

        // Regras com parâmetro, ex: min:6, max:64, in:a,b,c
        $param = null;
        if (str_contains($rule, ':')) {
            [$rule, $param] = explode(':', $rule, 2);
        }

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, "O campo {$fieldName} é obrigatório.");
                }
                break;

            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "O campo {$fieldName} deve ser um e-mail válido.");
                }
                break;

            case 'min':
                if ($value !== null && $value !== '') {
                    $length = is_string($value) ? mb_strlen($value) : $value;
                    if ($length < (int) $param) {
                        $this->addError($field, "O campo {$fieldName} deve ter no mínimo {$param} caracteres.");
                    }
                }
                break;

            case 'max':
                if ($value !== null && $value !== '') {
                    $length = is_string($value) ? mb_strlen($value) : $value;
                    if ($length > (int) $param) {
                        $this->addError($field, "O campo {$fieldName} deve ter no máximo {$param} caracteres.");
                    }
                }
                break;

            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->addError($field, "O campo {$fieldName} deve ser numérico.");
                }
                break;

            case 'integer':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "O campo {$fieldName} deve ser um número inteiro.");
                }
                break;

            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, "O campo {$fieldName} deve ser um texto.");
                }
                break;

            case 'boolean':
                if ($value !== null && !in_array($value, [true, false, 0, 1, '0', '1'], true)) {
                    $this->addError($field, "O campo {$fieldName} deve ser verdadeiro ou falso.");
                }
                break;

            case 'in':
                $allowed = explode(',', (string) $param);
                if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
                    $this->addError($field, "O campo {$fieldName} deve ser um dos valores: {$param}.");
                }
                break;

            case 'confirmed':
                $confirmField = "{$field}_confirmation";
                if (($body[$confirmField] ?? null) !== $value) {
                    $this->addError($field, "A confirmação do campo {$fieldName} não confere.");
                }
                break;

            case 'regex':
                if ($value !== null && $value !== '' && !preg_match($param, $value)) {
                    $this->addError($field, "O campo {$fieldName} possui formato inválido.");
                }
                break;
        }
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}