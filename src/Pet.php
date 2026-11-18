<?php

require_once __DIR__ . '/EntidadeBase.php';

/**
 * Classe Pet
 * Representa um paciente animal submetido ao acompanhamento clínico da PetCare.
 */
class Pet extends EntidadeBase
{
    public const ESPECIES_VALIDAS = ['cachorro', 'gato', 'ave', 'roedor', 'outro'];

    private string $nome;
    private string $especie;
    private ?string $raca;
    private string $dataNascimento;
    private float $pesoKg;
    private int $tutorId;
    private ?string $tutorNome;

    private bool $nomeTipoValido;
    private bool $especieTipoValido;
    private bool $racaTipoValido;
    private bool $pesoTipoValido;
    private bool $dataTipoValido;
    private bool $tutorIdTipoValido;

    public function __construct(
        string $nome,
        string $especie,
        ?string $raca,
        string $dataNascimento,
        float $pesoKg,
        int $tutorId,
        ?int $id = null,
        ?string $tutorNome = null,
        bool $nomeTipoValido = true,
        bool $especieTipoValido = true,
        bool $racaTipoValido = true,
        bool $pesoTipoValido = true,
        bool $dataTipoValido = true,
        bool $tutorIdTipoValido = true
    ) {
        parent::__construct($id);
        $this->nome = trim($nome);
        $this->especie = $especie;
        $this->raca = ($raca !== null && trim($raca) !== '') ? trim($raca) : null;
        $this->dataNascimento = $dataNascimento;
        $this->pesoKg = $pesoKg;
        $this->tutorId = $tutorId;
        $this->tutorNome = $tutorNome;

        $this->nomeTipoValido = $nomeTipoValido;
        $this->especieTipoValido = $especieTipoValido;
        $this->racaTipoValido = $racaTipoValido;
        $this->pesoTipoValido = $pesoTipoValido;
        $this->dataTipoValido = $dataTipoValido;
        $this->tutorIdTipoValido = $tutorIdTipoValido;
    }

    public static function criarDeRequisicao(array $dados, ?int $id = null): self
    {
        return self::construirDeDados($dados, $id, false);
    }

    public static function criarDeArray(array $dados, ?int $id = null): self
    {
        return self::construirDeDados($dados, $id, true);
    }

    private static function construirDeDados(array $dados, ?int $id, bool $aceitarMetadadosBanco): self
    {
        $idResolvido = $id ?? ($aceitarMetadadosBanco && isset($dados['id']) ? (int) $dados['id'] : null);
        $nomeValido = isset($dados['nome']) && is_string($dados['nome']);
        $especieValida = isset($dados['especie']) && is_string($dados['especie']);

        $racaValida = true;
        $racaValor = null;
        if (array_key_exists('raca', $dados) && $dados['raca'] !== null) {
            if (is_string($dados['raca'])) {
                $racaValor = $dados['raca'];
            } else {
                $racaValida = false;
            }
        }

        $pesoValido = isset($dados['peso_kg']) && (is_int($dados['peso_kg']) || is_float($dados['peso_kg']));
        $dataValida = isset($dados['data_nascimento']) && is_string($dados['data_nascimento']);
        $tutorIdValido = isset($dados['tutor_id']) && is_int($dados['tutor_id']);
        $tutorNome = ($aceitarMetadadosBanco && isset($dados['tutor_nome']) && is_string($dados['tutor_nome']))
            ? $dados['tutor_nome']
            : null;

        return new self(
            $nomeValido ? $dados['nome'] : '',
            $especieValida ? $dados['especie'] : '',
            $racaValor,
            $dataValida ? $dados['data_nascimento'] : '',
            $pesoValido ? (float) $dados['peso_kg'] : 0.0,
            $tutorIdValido ? $dados['tutor_id'] : 0,
            $idResolvido,
            $tutorNome,
            $nomeValido,
            $especieValida,
            $racaValida,
            $pesoValido,
            $dataValida,
            $tutorIdValido
        );
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getEspecie(): string
    {
        return $this->especie;
    }

    public function getRaca(): ?string
    {
        return $this->raca;
    }

    public function getDataNascimento(): string
    {
        return $this->dataNascimento;
    }

    public function getPesoKg(): float
    {
        return $this->pesoKg;
    }

    public function getTutorId(): int
    {
        return $this->tutorId;
    }

    public function getTutorNome(): ?string
    {
        return $this->tutorNome;
    }

    public static function especieValida(string $especie): bool
    {
        return in_array($especie, self::ESPECIES_VALIDAS, true);
    }

    public function validar(? callable $verificarTutorExistente = null): array
    {
        $erros = [];

        if (!$this->nomeTipoValido || !preg_match('/^.{2,60}$/u', $this->nome)) {
            $erros[] = 'O campo nome é obrigatório e deve ter entre 2 e 60 caracteres.';
        }

        if (!$this->especieTipoValido || !self::especieValida($this->especie)) {
            $erros[] = 'O campo especie deve ser um dos valores: cachorro, gato, ave, roedor ou outro.';
        }

        if (!$this->racaTipoValido || ($this->raca !== null && !preg_match('/^.{0,60}$/u', $this->raca))) {
            $erros[] = 'O campo raca deve ser texto com no máximo 60 caracteres ou nulo.';
        }

        if (
            !$this->pesoTipoValido
            || !is_finite($this->pesoKg)
            || $this->pesoKg < 0.01
            || $this->pesoKg > 200
        ) {
            $erros[] = 'O campo peso_kg é obrigatório e deve ser um número entre 0.01 e 200.';
        }

        if (!$this->dataTipoValido) {
            $erros[] = 'O campo data_nascimento é obrigatório no formato AAAA-MM-DD.';
        } else {
            $dt = DateTime::createFromFormat('Y-m-d', $this->dataNascimento);
            $errosData = DateTime::getLastErrors();
            $dataCalendarioValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dataNascimento) === 1
                && $dt !== false
                && ($errosData === false || ($errosData['warning_count'] === 0 && $errosData['error_count'] === 0))
                && $dt->format('Y-m-d') === $this->dataNascimento
                && $this->dataNascimento >= '1000-01-01';

            if (!$dataCalendarioValida) {
                $erros[] = 'O campo data_nascimento deve ser uma data válida no formato AAAA-MM-DD.';
            } elseif ($this->dataNascimento > date('Y-m-d')) {
                $erros[] = 'O campo data_nascimento não pode ser uma data futura.';
            }
        }

        if (!$this->tutorIdTipoValido || $this->tutorId <= 0) {
            $erros[] = 'O campo tutor_id é obrigatório e deve ser um número inteiro positivo.';
        } elseif ($verificarTutorExistente !== null && !$verificarTutorExistente($this->tutorId)) {
            $erros[] = 'O tutor_id informado não existe.';
        }

        return $erros;
    }

    public function toArray(): array
    {
        $dados = [
            'id' => (int) $this->id,
            'nome' => $this->nome,
            'especie' => $this->especie,
            'raca' => $this->raca,
            'data_nascimento' => $this->dataNascimento,
            'peso_kg' => $this->pesoKg,
            'tutor_id' => $this->tutorId,
        ];

        if ($this->tutorNome !== null) {
            $dados['tutor_nome'] = $this->tutorNome;
        }

        return $dados;
    }
}
