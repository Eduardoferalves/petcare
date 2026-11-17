<?php

require_once __DIR__ . '/RepositorioBase.php';
require_once __DIR__ . '/Tutor.php';
require_once __DIR__ . '/Http.php';

/**
 * Classe TutorRepositorio
 * Responsável pelas operações de persistência CRUD da entidade Tutor via PDO.
 */
class TutorRepositorio extends RepositorioBase
{
    public function __construct(
        PDO $pdo,
        private Http $http
    ) {
        parent::__construct($pdo);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarTodos(): array
    {
        $stmt = $this->pdo->query('SELECT id, nome, telefone, email FROM tutores ORDER BY id ASC');
        $linhas = $stmt->fetchAll();

        $tutores = [];
        foreach ($linhas as $linha) {
            $tutor = Tutor::criarDeArray($linha);
            $tutores[] = $tutor->toArray();
        }

        return $tutores;
    }

    public function buscarPorId(int $id): ?Tutor
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, telefone, email FROM tutores WHERE id = ?');
        $stmt->execute([$id]);
        $linha = $stmt->fetch();

        if (!$linha) {
            return null;
        }

        return Tutor::criarDeArray($linha);
    }

    public function existe(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM tutores WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetch();
    }

    public function criar(Tutor $tutor): Tutor
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO tutores (nome, telefone, email) VALUES (?, ?, ?)'
            );
            $stmt->execute([
                $tutor->getNome(),
                $tutor->getTelefone(),
                $tutor->getEmail(),
            ]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                $this->http->responder(['detail' => 'E-mail já cadastrado'], 409);
            }
            throw $e;
        }

        $tutor->setId((int) $this->pdo->lastInsertId());
        return $tutor;
    }

    public function atualizar(Tutor $tutor): Tutor
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE tutores SET nome = ?, telefone = ?, email = ? WHERE id = ?'
            );
            $stmt->execute([
                $tutor->getNome(),
                $tutor->getTelefone(),
                $tutor->getEmail(),
                $tutor->getId(),
            ]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                $this->http->responder(['detail' => 'E-mail já cadastrado'], 409);
            }
            throw $e;
        }

        return $tutor;
    }

    public function excluir(int $id): void
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM tutores WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1451 || ($e->errorInfo[1] ?? null) === 1452) {
                $this->http->responder(['detail' => 'Não é possível excluir tutor que possui pets vinculados'], 409);
            }
            throw $e;
        }
    }
}
