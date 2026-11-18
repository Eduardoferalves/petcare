<?php

require_once __DIR__ . '/Validavel.php';
require_once __DIR__ . '/Serializavel.php';

/**
 * Classe abstrata EntidadeBase
 * Superclasse para todas as entidades de domínio do sistema (Tutor, Pet).
 * Demonstra herança, visibilidade protected, abstração e implementação de múltiplas interfaces.
 */
abstract class EntidadeBase implements Validavel, Serializavel
{
    protected ?int $id;

    public function __construct(?int $id = null)
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('O id da entidade deve ser um número inteiro positivo.');
        }
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('O id da entidade deve ser um número inteiro positivo.');
        }
        $this->id = $id;
    }

    abstract public function validar(): array;

    abstract public function toArray(): array;
}
