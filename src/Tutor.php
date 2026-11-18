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
    private bool $nomeTipoValido;
    private bool $telefoneTipoValido;
    private bool $emailTipoValido;

    public function __construct(
        string $nome,
        string $telefone,
        string $email,
        ?int $id = null,
        bool $nomeTipoValido = true,
        bool $telefoneTipoValido = true,
        bool $emailTipoValido = true
    ) {
        parent::__construct($id);
        $this->nome = trim($nome);
        $this->telefone = trim($telefone);
        $this->email = trim($email);
        $this->nomeTipoValido = $nomeTipoValido;
        $this->telefoneTipoValido = $telefoneTipoValido;
        $this->emailTipoValido = $emailTipoValido;
    }

    public static function criarDeRequisicao(array $dados, ?int $id = null): self
    {
        return self::construirDeDados($dados, $id, false);
    }

    public static function criarDeArray(array $dados, ?int $id = null): self
    {
        return self::construirDeDados($dados, $id, true);
    }

    private static function construirDeDados(array $dados, ?int $id, bool $aceitarIdArray): self
    {
        $idResolvido = $id ?? ($aceitarIdArray && isset($dados['id']) ? (int) $dados['id'] : null);
        $nomeValido = isset($dados['nome']) && is_string($dados['nome']);
        $telefoneValido = isset($dados['telefone']) && is_string($dados['telefone']);
        $emailValido = isset($dados['email']) && is_string($dados['email']);

        return new self(
            $nomeValido ? $dados['nome'] : '',
            $telefoneValido ? $dados['telefone'] : '',
            $emailValido ? $dados['email'] : '',
            $idResolvido,
            $nomeValido,
            $telefoneValido,
            $emailValido
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

        if (!$this->nomeTipoValido || !preg_match('/^.{2,100}$/u', $this->nome)) {
            $erros[] = 'O campo nome é obrigatório e deve ter entre 2 e 100 caracteres.';
        }

        if (!$this->telefoneTipoValido || !preg_match('/^.{1,30}$/u', $this->telefone)) {
            $erros[] = 'O campo telefone é obrigatório e deve ter até 30 caracteres.';
        }

        if (
            !$this->emailTipoValido
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
            'id' => (int) $this->id,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'email' => $this->email,
        ];
    }
}
