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
    private bool $emailTipoValido;
    private bool $senhaTipoValido;

    public function __construct(
        string $email,
        string $senha,
        bool $emailTipoValido = true,
        bool $senhaTipoValido = true
    ) {
        $this->email = trim($email);
        $this->senha = $senha;
        $this->emailTipoValido = $emailTipoValido;
        $this->senhaTipoValido = $senhaTipoValido;
    }

    public static function criarDeArray(array $dados): self
    {
        $emailValido = isset($dados['email']) && is_string($dados['email']);
        $senhaValida = isset($dados['senha']) && is_string($dados['senha']);

        return new self(
            $emailValido ? $dados['email'] : '',
            $senhaValida ? $dados['senha'] : '',
            $emailValido,
            $senhaValida
        );
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

        if (!$this->emailTipoValido || $this->email === '') {
            $erros[] = 'O campo email é obrigatório.';
        } elseif (
            filter_var($this->email, FILTER_VALIDATE_EMAIL) === false
            || !preg_match('/^.{1,150}$/u', $this->email)
        ) {
            $erros[] = 'O campo email deve conter um endereço de e-mail válido com até 150 caracteres.';
        }

        if (!$this->senhaTipoValido || $this->senha === '') {
            $erros[] = 'O campo senha é obrigatório.';
        } elseif (strlen($this->senha) > 255) {
            $erros[] = 'O campo senha deve ter no máximo 255 caracteres.';
        }

        return $erros;
    }
}
