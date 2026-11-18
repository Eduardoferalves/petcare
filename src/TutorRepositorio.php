<?php

require_once __DIR__ . '/RepositorioBase.php';
require_once __DIR__ . '/Tutor.php';
require_once __DIR__ . '/ApiException.php';

/**
 * Classe TutorRepositorio
 * Responsável pelas operações de persistência CRUD da entidade Tutor via PDO.
 */
class TutorRepositorio extends RepositorioBase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarTodos(): array
    {
        $stmt = $this->pdo->query('SELECT id, nome, telefone, email FROM tutores ORDER BY id ASC');
        $linhas = $stmt->fetchAll();

        $tutores = [];
        foreach ($linhas as $linha) {
            $tutores[] = Tutor::deLinhaBanco($linha)->toArray();
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

        return Tutor::deLinhaBanco($linha);
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
                throw ApiException::conflito('E-mail já cadastrado');
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
                throw ApiException::conflito('E-mail já cadastrado');
            }
            throw $e;
        }

        if ($stmt->rowCount() === 0) {
            throw ApiException::naoEncontrado('Tutor não encontrado');
        }

        return $tutor;
    }

    public function excluir(int $id): void
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM tutores WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1451) {
                throw ApiException::conflito('Não é possível excluir tutor que possui pets vinculados');
            }
            throw $e;
        }

        if ($stmt->rowCount() === 0) {
            throw ApiException::naoEncontrado('Tutor não encontrado');
        }
    }
}
