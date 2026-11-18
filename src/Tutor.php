<?php

require_once __DIR__ . '/EntidadeBase.php';

/**
 * Classe Tutor
 * Representa o cliente responsável pelos pets na clínica PetCare.
 */
class Tutor extends EntidadeBase
{
    private string $nome;
    private string $telefone;
    private string $email;
    /** @var array<string, bool> */
    private array $camposTipoInvalido = [];

    public function __construct(
        string $nome,
        string $telefone,
        string $email,
        ?int $id = null
    ) {
        parent::__construct($id);
        $this->nome = trim($nome);
        $this->telefone = trim($telefone);
        $this->email = trim($email);
    }

    public static function criarDeRequisicao(array $dados, ?int $id = null): self
    {
        $camposInvalidos = [];

        $nomeValido = isset($dados['nome']) && is_string($dados['nome']);
        if (!$nomeValido) {
            $camposInvalidos['nome'] = true;
        }

        $telefoneValido = isset($dados['telefone']) && is_string($dados['telefone']);
        if (!$telefoneValido) {
            $camposInvalidos['telefone'] = true;
        }

        $emailValido = isset($dados['email']) && is_string($dados['email']);
        if (!$emailValido) {
            $camposInvalidos['email'] = true;
        }

        $tutor = new self(
            $nomeValido ? $dados['nome'] : '',
            $telefoneValido ? $dados['telefone'] : '',
            $emailValido ? $dados['email'] : '',
            $id
        );
        $tutor->camposTipoInvalido = $camposInvalidos;

        return $tutor;
    }

    public static function deLinhaBanco(array $linha): self
    {
        return new self(
            (string) $linha['nome'],
            (string) $linha['telefone'],
            (string) $linha['email'],
            (int) $linha['id']
        );
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getTelefone(): string
    {
        return $this->telefone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function validar(): array
    {
        $erros = [];

        if (isset($this->camposTipoInvalido['nome']) || !preg_match('/^.{2,100}$/u', $this->nome)) {
            $erros[] = 'O campo nome é obrigatório e deve ter entre 2 e 100 caracteres.';
        }

        if (isset($this->camposTipoInvalido['telefone']) || !preg_match('/^.{1,30}$/u', $this->telefone)) {
            $erros[] = 'O campo telefone é obrigatório e deve ter até 30 caracteres.';
        }

        if (
            isset($this->camposTipoInvalido['email'])
            || filter_var($this->email, FILTER_VALIDATE_EMAIL) === false
            || !preg_match('/^.{1,150}$/u', $this->email)
        ) {
            $erros[] = 'O campo email é obrigatório e deve ser um endereço de e-mail válido com até 150 caracteres.';
        }

        return $erros;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'email' => $this->email,
        ];
    }
}
