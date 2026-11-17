<?php

/**
 * Classe abstrata RepositorioBase
 * Superclasse para os repositórios e serviços que operam sobre a conexão PDO.
 */
abstract class RepositorioBase
{
    public function __construct(
        protected PDO $pdo
    ) {}
}
