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
    public function __construct(
        protected ?int $id = null
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    abstract public function validar(): array;

    abstract public function toArray(): array;
}
