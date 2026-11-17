<?php

require_once __DIR__ . '/Serializavel.php';
require_once __DIR__ . '/Validavel.php';

/**
 * Classe final Http
 * Centraliza a leitura de JSON da requisição, serialização polimórfica de respostas e envio de status HTTP.
 */
final class Http
{
    public const OPCOES_JSON = JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * Envia uma resposta HTTP padronizada em JSON (ou sem corpo para 204) e encerra a execução.
     * Suporta polimorfismo: aceita objetos que implementam Serializavel e os converte automaticamente.
     */
    public function responder(mixed $dados = null, int $status = 200): void
    {
        http_response_code($status);
        if ($status === 204) {
            exit;
        }

        if ($dados instanceof Serializavel) {
            $dados = $dados->toArray();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, self::OPCOES_JSON);
        exit;
    }

    /**
     * Lê e decodifica o corpo JSON da requisição.
     * Caso o corpo não seja um JSON válido, responde imediatamente com 400 Bad Request.
     */
    public function lerCorpoJson(): array
    {
        $corpo = json_decode(file_get_contents('php://input'), true);
        if (!is_array($corpo)) {
            $this->responder(['detail' => 'O corpo da requisição deve ser um JSON válido'], 400);
        }
        return $corpo;
    }

    /**
     * Valida polimorficamente qualquer objeto que implemente o contrato Validavel.
     * Caso existam erros, encerra a requisição com HTTP 422 Unprocessable Entity.
     */
    public function validarOuResponderErro(Validavel $objeto): void
    {
        $erros = $objeto->validar();
        if (!empty($erros)) {
            $this->responder(['detail' => $erros], 422);
        }
    }
}
