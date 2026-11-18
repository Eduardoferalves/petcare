<?php

/**
 * Classe final BancoDados
 * Encapsula a configuração e criação da conexão PDO com o banco MySQL petcare.
 */
final class BancoDados
{
    private const ERROS_CONEXAO_REDE = [2002, 2003, 2006];

    private string $host;
    private string $banco;
    private string $usuario;
    private string $senha;

    public function __construct(
        ?string $host = null,
        ?string $banco = null,
        ?string $usuario = null,
        ?string $senha = null
    ) {
        $this->host = $host ?? (getenv('DB_HOST') ?: 'localhost');
        $this->banco = $banco ?? (getenv('DB_NAME') ?: 'petcare');
        $this->usuario = $usuario ?? (getenv('DB_USER') ?: 'root');
        $envPass = getenv('DB_PASS');
        $this->senha = $senha ?? ($envPass !== false ? $envPass : '');
    }

    public function conectar(): PDO
    {
        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ];

        $portaEnv = getenv('DB_PORT');
        $portas = ($portaEnv !== false && $portaEnv !== '') ? [$portaEnv] : ['3306', '3307'];

        $pdo = null;
        $ultimoErro = null;

        foreach ($portas as $indice => $porta) {
            try {
                $pdo = new PDO(
                    "mysql:host={$this->host};port={$porta};dbname={$this->banco};charset=utf8mb4",
                    $this->usuario,
                    $this->senha,
                    $opcoes
                );
                break;
            } catch (PDOException $e) {
                $ultimoErro = $e;
                $codigoDriver = $e->errorInfo[1] ?? null;
                $ehUltimo = ($indice === count($portas) - 1);
                if ($ehUltimo || !in_array($codigoDriver, self::ERROS_CONEXAO_REDE, true)) {
                    error_log('[PetCare DB] Falha ao conectar na porta ' . $porta . ': ' . $e->getMessage());
                    throw new RuntimeException('Erro de conexão com o banco de dados.', 0, $e);
                }
            }
        }

        if ($pdo === null) {
            throw new RuntimeException('Erro de conexão com o banco de dados.', 0, $ultimoErro);
        }

        $pdo->exec("SET time_zone = '-03:00', sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

        return $pdo;
    }
}
