<?php

/**
 * Interface Validavel
 * Contrato para objetos capazes de validar suas próprias regras de negócio e retornar uma lista de erros.
 */
interface Validavel
{
    public function validar(): array;
}
