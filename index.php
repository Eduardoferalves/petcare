<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/src/Http.php';
require_once __DIR__ . '/src/BancoDados.php';
require_once __DIR__ . '/src/CredencialLogin.php';
require_once __DIR__ . '/src/Tutor.php';
require_once __DIR__ . '/src/Pet.php';
require_once __DIR__ . '/src/AutenticacaoServico.php';
require_once __DIR__ . '/src/TutorRepositorio.php';
require_once __DIR__ . '/src/PetRepositorio.php';

// Instanciação dos objetos principais da aplicação (Operador new)
$http = new Http();
$banco = new BancoDados($http);
$pdo = $banco->conectar();

$autenticacao = new AutenticacaoServico($pdo, $http);
$tutorRepositorio = new TutorRepositorio($pdo, $http);
$petRepositorio = new PetRepositorio($pdo, $http);

// Extração do método HTTP e caminho relativo da rota
$metodo = $_SERVER['REQUEST_METHOD'];
$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base !== '' && str_starts_with($caminho, $base)) {
    $caminho = substr($caminho, strlen($base)) ?: '/';
}
if ($caminho !== '/') {
    $caminho = rtrim($caminho, '/');
}

// ============================================================================
// 1. AUTENTICAÇÃO: POST /login e POST /logout
// ============================================================================

if ($metodo === 'POST' && $caminho === '/login') {
    $corpo = $http->lerCorpoJson();
    $credencial = CredencialLogin::criarDeArray($corpo);
    $dadosSessao = $autenticacao->login($credencial);

    $http->responder($dadosSessao, 200);
}

if ($metodo === 'POST' && $caminho === '/logout') {
    $autenticacao->logout();
    $http->responder(null, 204);
}

// ============================================================================
// 2. ROTAS DE TUTORES
// ============================================================================

// GET /tutores - Lista todos os tutores (Pública)
if ($metodo === 'GET' && $caminho === '/tutores') {
    $tutores = $tutorRepositorio->listarTodos();

    $http->responder([
        'total' => count($tutores),
        'tutores' => $tutores,
    ], 200);
}

// GET /tutores/{id} - Busca um tutor pelo ID (Pública)
if ($metodo === 'GET' && preg_match('#^/tutores/(\d+)$#', $caminho, $partes)) {
    $id = (int) $partes[1];
    $tutor = $tutorRepositorio->buscarPorId($id);

    if ($tutor === null) {
        $http->responder(['detail' => 'Tutor não encontrado'], 404);
    }

    // Polimorfismo: Http::responder aceita qualquer objeto Serializavel
    $http->responder($tutor, 200);
}

// GET /tutores/{id}/pets - Lista os pets de um tutor específico (Pública)
if ($metodo === 'GET' && preg_match('#^/tutores/(\d+)/pets$#', $caminho, $partes)) {
    $tutorId = (int) $partes[1];
    $tutor = $tutorRepositorio->buscarPorId($tutorId);

    if ($tutor === null) {
        $http->responder(['detail' => 'Tutor não encontrado'], 404);
    }

    $pets = $petRepositorio->listarPorTutor($tutorId);

    $http->responder([
        'tutor_id' => $tutor->getId(),
        'tutor_nome' => $tutor->getNome(),
        'total' => count($pets),
        'pets' => $pets,
    ], 200);
}

// POST /tutores - Cadastra um novo tutor (Requer Token)
if ($metodo === 'POST' && $caminho === '/tutores') {
    $autenticacao->exigirToken();
    $corpo = $http->lerCorpoJson();

    $tutor = Tutor::criarDeArray($corpo);
    $http->validarOuResponderErro($tutor);

    $tutorCriado = $tutorRepositorio->criar($tutor);
    $http->responder($tutorCriado, 201);
}

// PUT /tutores/{id} - Atualiza um tutor existente (Requer Token)
if ($metodo === 'PUT' && preg_match('#^/tutores/(\d+)$#', $caminho, $partes)) {
    $autenticacao->exigirToken();
    $id = (int) $partes[1];
    $corpo = $http->lerCorpoJson();

    if (!$tutorRepositorio->existe($id)) {
        $http->responder(['detail' => 'Tutor não encontrado'], 404);
    }

    $tutor = Tutor::criarDeArray($corpo, $id);
    $http->validarOuResponderErro($tutor);

    $tutorAtualizado = $tutorRepositorio->atualizar($tutor);
    $http->responder($tutorAtualizado, 200);
}

// DELETE /tutores/{id} - Exclui um tutor (Requer Token)
if ($metodo === 'DELETE' && preg_match('#^/tutores/(\d+)$#', $caminho, $partes)) {
    $autenticacao->exigirToken();
    $id = (int) $partes[1];

    if (!$tutorRepositorio->existe($id)) {
        $http->responder(['detail' => 'Tutor não encontrado'], 404);
    }

    $tutorRepositorio->excluir($id);
    $http->responder(null, 204);
}

// ============================================================================
// 3. ROTAS DE PETS
// ============================================================================

// GET /pets - Lista todos os pets com filtro opcional ?especie= (Pública)
if ($metodo === 'GET' && $caminho === '/pets') {
    $especieFiltro = null;

    if (isset($_GET['especie']) && $_GET['especie'] !== '') {
        $especieFiltro = $_GET['especie'];
        if (!Pet::especieValida($especieFiltro)) {
            $http->responder([
                'detail' => [
                    'O filtro especie deve ser um dos valores: cachorro, gato, ave, roedor ou outro.',
                ],
            ], 422);
        }
    }

    $pets = $petRepositorio->listarTodos($especieFiltro);

    $http->responder([
        'total' => count($pets),
        'pets' => $pets,
    ], 200);
}

// GET /pets/{id} - Busca um pet pelo ID com o nome do tutor via JOIN (Pública)
if ($metodo === 'GET' && preg_match('#^/pets/(\d+)$#', $caminho, $partes)) {
    $id = (int) $partes[1];
    $pet = $petRepositorio->buscarPorId($id);

    if ($pet === null) {
        $http->responder(['detail' => 'Pet não encontrado'], 404);
    }

    $http->responder($pet, 200);
}

// POST /pets - Cadastra um novo pet (Requer Token)
if ($metodo === 'POST' && $caminho === '/pets') {
    $autenticacao->exigirToken();
    $corpo = $http->lerCorpoJson();

    $pet = Pet::criarDeArray($corpo);
    $erros = $pet->validar(fn(int $tutorId): bool => $tutorRepositorio->existe($tutorId));
    if (!empty($erros)) {
        $http->responder(['detail' => $erros], 422);
    }

    $petCriado = $petRepositorio->criar($pet);
    $http->responder($petCriado, 201);
}

// PUT /pets/{id} - Atualiza um pet existente (Requer Token)
if ($metodo === 'PUT' && preg_match('#^/pets/(\d+)$#', $caminho, $partes)) {
    $autenticacao->exigirToken();
    $id = (int) $partes[1];
    $corpo = $http->lerCorpoJson();

    if (!$petRepositorio->existe($id)) {
        $http->responder(['detail' => 'Pet não encontrado'], 404);
    }

    $pet = Pet::criarDeArray($corpo, $id);
    $erros = $pet->validar(fn(int $tutorId): bool => $tutorRepositorio->existe($tutorId));
    if (!empty($erros)) {
        $http->responder(['detail' => $erros], 422);
    }

    $petAtualizado = $petRepositorio->atualizar($pet);
    $http->responder($petAtualizado, 200);
}

// DELETE /pets/{id} - Exclui um pet (Requer Token)
if ($metodo === 'DELETE' && preg_match('#^/pets/(\d+)$#', $caminho, $partes)) {
    $autenticacao->exigirToken();
    $id = (int) $partes[1];

    if (!$petRepositorio->existe($id)) {
        $http->responder(['detail' => 'Pet não encontrado'], 404);
    }

    $petRepositorio->excluir($id);
    $http->responder(null, 204);
}

// ============================================================================
// 4. FALLBACK PARA ROTAS NÃO ENCONTRADAS
// ============================================================================

$http->responder(['detail' => 'Rota não encontrada'], 404);
