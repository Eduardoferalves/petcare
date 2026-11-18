<?php

/**
 * Classe ApiException
 * Exceção de aplicação/domínio que carrega o status HTTP e o detalhe estruturado (detail).
 */
class ApiException extends RuntimeException
{
    /**
     * @param string|array<int, string> $detail
     */
    public function __construct(
        private int $status,
        private string|array $detail
    ) {
        $mensagem = is_string($detail) ? $detail : implode(' ', $detail);
        parent::__construct($mensagem, $status);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return string|array<int, string>
     */
    public function getDetail(): string|array
    {
        return $this->detail;
    }

    public static function requisicaoInvalida(string $mensagem): self
    {
        return new self(400, $mensagem);
    }

    public static function naoAutenticado(string $mensagem): self
    {
        return new self(401, $mensagem);
    }

    public static function naoEncontrado(string $mensagem): self
    {
        return new self(404, $mensagem);
    }

    public static function conflito(string $mensagem): self
    {
        return new self(409, $mensagem);
    }

    /**
     * @param array<int, string> $erros
     */
    public static function validacao(array $erros): self
    {
        return new self(422, $erros);
    }
}
