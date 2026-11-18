<?php

require_once __DIR__ . '/Validavel.php';

/**
 * Classe CredencialLogin
 * Encapsula os dados de entrada da rota POST /login e implementa o contrato Validavel.
 */
class CredencialLogin implements Validavel
{
    private string $email;
    private string $senha;
    /** @var array<string, bool> */
    private array $camposTipoInvalido = [];

    public function __construct(string $email, string $senha)
    {
        $this->email = trim($email);
        $this->senha = $senha;
    }

    public static function criarDeRequisicao(array $dados): self
    {
        $camposInvalidos = [];

        $emailValido = isset($dados['email']) && is_string($dados['email']);
        if (!$emailValido) {
            $camposInvalidos['email'] = true;
        }

        $senhaValida = isset($dados['senha']) && is_string($dados['senha']);
        if (!$senhaValida) {
            $camposInvalidos['senha'] = true;
        }

        $credencial = new self(
            $emailValido ? $dados['email'] : '',
            $senhaValida ? $dados['senha'] : ''
        );
        $credencial->camposTipoInvalido = $camposInvalidos;

        return $credencial;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSenha(): string
    {
        return $this->senha;
    }

    public function validar(): array
    {
        $erros = [];

        if (isset($this->camposTipoInvalido['email']) || $this->email === '') {
            $erros[] = 'O campo email é obrigatório.';
        } elseif (
            filter_var($this->email, FILTER_VALIDATE_EMAIL) === false
            || !preg_match('/^.{1,150}$/u', $this->email)
        ) {
            $erros[] = 'O campo email deve conter um endereço de e-mail válido com até 150 caracteres.';
        }

        if (isset($this->camposTipoInvalido['senha']) || $this->senha === '') {
            $erros[] = 'O campo senha é obrigatório.';
        } elseif (strlen($this->senha) > 255) {
            $erros[] = 'O campo senha deve ter no máximo 255 caracteres.';
        }

        return $erros;
    }
}
