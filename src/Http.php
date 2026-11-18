<?php

require_once __DIR__ . '/Serializavel.php';
require_once __DIR__ . '/Validavel.php';
require_once __DIR__ . '/ApiException.php';

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

    private const MAX_BYTES_CORPO = 1048576;

    /**
     * Envia uma resposta HTTP padronizada em JSON (ou sem corpo para 204) e encerra a execução.
     * Suporta polimorfismo: aceita objetos que implementam Serializavel e os converte automaticamente.
     */
    public function responder(mixed $dados = null, int $status = 200): void
    {
        http_response_code($status);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-store');

        if ($status === 204) {
            exit;
        }

        if ($dados instanceof Serializavel) {
            $dados = $dados->toArray();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, self::OPCOES_JSON | JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * Lê e decodifica o corpo JSON como objeto associativo.
     * Caso o corpo exceda o limite, seja inválido ou não represente um objeto JSON, lança ApiException 400.
     */
    public function lerCorpoJson(): array
    {
        $bruto = (string) file_get_contents('php://input');
        if (strlen($bruto) > self::MAX_BYTES_CORPO) {
            throw ApiException::requisicaoInvalida('O corpo da requisição excede o tamanho máximo permitido');
        }

        $objeto = json_decode($bruto, false);
        if (json_last_error() !== JSON_ERROR_NONE || !$objeto instanceof stdClass) {
            throw ApiException::requisicaoInvalida('O corpo da requisição deve ser um JSON válido');
        }

        return json_decode($bruto, true);
    }

    /**
     * Valida polimorficamente qualquer objeto que implemente o contrato Validavel.
     * Caso existam erros, lança ApiException 422 com a lista acumulada.
     *
     * @param array<int, string> $errosAdicionais
     */
    public function validarOuResponderErro(Validavel $objeto, array $errosAdicionais = []): void
    {
        $erros = array_merge($objeto->validar(), $errosAdicionais);
        if (!empty($erros)) {
            throw ApiException::validacao($erros);
        }
    }
}
