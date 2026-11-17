<?php

require_once __DIR__ . '/Http.php';

/**
 * Classe final BancoDados
 * Encapsula a configuração e criação da conexão PDO com o banco MySQL petcare.
 */
final class BancoDados
{
    public function __construct(
        private Http $http,
        private string $host = 'localhost',
        private string $banco = 'petcare',
        private string $usuario = 'root',
        private string $senha = ''
    ) {}

    public function conectar(): PDO
    {
        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $pdo = new PDO(
                "mysql:host={$this->host};port=3306;dbname={$this->banco};charset=utf8mb4",
                $this->usuario,
                $this->senha,
                $opcoes
            );
        } catch (PDOException $e) {
            try {
                $pdo = new PDO(
                    "mysql:host={$this->host};port=3307;dbname={$this->banco};charset=utf8mb4",
                    $this->usuario,
                    $this->senha,
                    $opcoes
                );
            } catch (PDOException $e2) {
                $this->http->responder([
                    'detail' => 'Erro de conexão com o banco de dados. Verifique se o MySQL está ativo e se o script banco.sql foi executado.',
                ], 500);
            }
        }

        $pdo->exec("SET time_zone = '-03:00'");

        return $pdo;
    }
}
