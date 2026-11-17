<?php

/**
 * Interface Serializavel
 * Contrato para objetos de domínio capazes de converter seu estado interno em array associativo para JSON.
 */
interface Serializavel
{
    public function toArray(): array;
}
