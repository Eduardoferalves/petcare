<?php

require_once __DIR__ . '/RepositorioBase.php';
require_once __DIR__ . '/Pet.php';
require_once __DIR__ . '/ApiException.php';

/**
 * Classe PetRepositorio
 * Responsável pelas operações de persistência CRUD da entidade Pet via PDO.
 */
class PetRepositorio extends RepositorioBase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarTodos(?string $especieFiltro = null): array
    {
        if ($especieFiltro !== null && $especieFiltro !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT p.id, p.nome, p.especie, p.raca, p.data_nascimento, p.peso_kg, p.tutor_id, t.nome AS tutor_nome
                 FROM pets p
                 INNER JOIN tutores t ON t.id = p.tutor_id
                 WHERE p.especie = ?
                 ORDER BY p.id ASC'
            );
            $stmt->execute([$especieFiltro]);
        } else {
            $stmt = $this->pdo->query(
                'SELECT p.id, p.nome, p.especie, p.raca, p.data_nascimento, p.peso_kg, p.tutor_id, t.nome AS tutor_nome
                 FROM pets p
                 INNER JOIN tutores t ON t.id = p.tutor_id
                 ORDER BY p.id ASC'
            );
        }

        $pets = [];
        foreach ($stmt->fetchAll() as $linha) {
            $pets[] = Pet::deLinhaBanco($linha)->toArray();
        }

        return $pets;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarPorTutor(int $tutorId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, especie, raca, data_nascimento, peso_kg, tutor_id
             FROM pets
             WHERE tutor_id = ?
             ORDER BY id ASC'
        );
        $stmt->execute([$tutorId]);

        $pets = [];
        foreach ($stmt->fetchAll() as $linha) {
            $pets[] = Pet::deLinhaBanco($linha)->toArray();
        }

        return $pets;
    }

    public function buscarPorId(int $id): ?Pet
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.nome, p.especie, p.raca, p.data_nascimento, p.peso_kg, p.tutor_id, t.nome AS tutor_nome
             FROM pets p
             INNER JOIN tutores t ON t.id = p.tutor_id
             WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        $linha = $stmt->fetch();

        if (!$linha) {
            return null;
        }

        return Pet::deLinhaBanco($linha);
    }

    public function existe(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM pets WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetch();
    }

    public function criar(Pet $pet): Pet
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO pets (nome, especie, raca, data_nascimento, peso_kg, tutor_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $pet->getNome(),
                $pet->getEspecie(),
                $pet->getRaca(),
                $pet->getDataNascimento(),
                $pet->getPesoKg(),
                $pet->getTutorId(),
            ]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1452) {
                throw ApiException::validacao(['O tutor_id informado não existe.']);
            }
            throw $e;
        }

        $pet->setId((int) $this->pdo->lastInsertId());
        return $pet;
    }

    public function atualizar(Pet $pet): Pet
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE pets
                 SET nome = ?, especie = ?, raca = ?, data_nascimento = ?, peso_kg = ?, tutor_id = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $pet->getNome(),
                $pet->getEspecie(),
                $pet->getRaca(),
                $pet->getDataNascimento(),
                $pet->getPesoKg(),
                $pet->getTutorId(),
                $pet->getId(),
            ]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1452) {
                throw ApiException::validacao(['O tutor_id informado não existe.']);
            }
            throw $e;
        }

        if ($stmt->rowCount() === 0) {
            throw ApiException::naoEncontrado('Pet não encontrado');
        }

        return $pet;
    }

    public function excluir(int $id): void
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM pets WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1451) {
                throw ApiException::conflito('Não é possível excluir pet vinculado a outros registros');
            }
            throw $e;
        }

        if ($stmt->rowCount() === 0) {
            throw ApiException::naoEncontrado('Pet não encontrado');
        }
    }
}
