<?php

require_once __DIR__ . '/CredencialLogin.php';
require_once __DIR__ . '/ApiException.php';

/**
 * Classe AutenticacaoServico
 * Gerencia a autenticação por Bearer Token via composição sobre PDO (login, verificação e logout).
 */
class AutenticacaoServico
{
    // Hash Bcrypt válido usado para igualar o custo de tempo quando o e-mail não existe
    private const DUMMY_BCRYPT_HASH = '$2y$10$VvctLq45NFgb07wuEksxNeArDBwUN8GSIJg0yWaRdJrP.Vbd3iZCW';

    public function __construct(
        private PDO $pdo
    ) {}

    /**
     * Lê o valor bruto do cabeçalho HTTP Authorization (ou null se ausente/vazio).
     */
    private function obterCabecalhoAuthorization(): ?string
    {
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $chave) {
            if (!empty($_SERVER[$chave]) && is_string($_SERVER[$chave]) && trim($_SERVER[$chave]) !== '') {
                return $_SERVER[$chave];
            }
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $nome => $valor) {
                    if (strcasecmp((string) $nome, 'Authorization') === 0 && is_string($valor) && trim($valor) !== '') {
                        return $valor;
                    }
                }
            }
        }

        return null;
    }

    private function extrairTokenDeCabecalho(string $cabecalho): ?string
    {
        if (preg_match('/^(?i:Bearer)\s+([0-9a-f]{64})$/', trim($cabecalho), $partes)) {
            return $partes[1];
        }

        return null;
    }

    /**
     * Extrai o token Bearer hexadecimal de 64 caracteres do cabeçalho HTTP Authorization.
     */
    public function extrairBearerToken(): ?string
    {
        $cabecalho = $this->obterCabecalhoAuthorization();
        if ($cabecalho === null) {
            return null;
        }

        return $this->extrairTokenDeCabecalho($cabecalho);
    }

    /**
     * Verifica se a requisição possui um token Bearer válido e não expirado.
     * Lança ApiException 401 caso o token não seja informado, seja malformado, não exista ou esteja expirado.
     */
    public function exigirToken(): array
    {
        $cabecalho = $this->obterCabecalhoAuthorization();
        if ($cabecalho === null) {
            throw ApiException::naoAutenticado('Token não informado');
        }

        $token = $this->extrairTokenDeCabecalho($cabecalho);
        if ($token === null) {
            throw ApiException::naoAutenticado('Token inválido ou expirado');
        }

        $stmt = $this->pdo->prepare(
            'SELECT t.id AS token_id, t.token, t.expira_em, u.id AS usuario_id, u.nome, u.email
             FROM tokens t
             INNER JOIN usuarios u ON u.id = t.usuario_id
             WHERE t.token = ? AND t.expira_em > NOW()'
        );
        $stmt->execute([$token]);
        $registro = $stmt->fetch();

        if (!$registro) {
            throw ApiException::naoAutenticado('Token inválido ou expirado');
        }

        return $registro;
    }

    /**
     * Realiza o login do operador, gera o token CSPRNG de 64 caracteres e grava validade usando o relógio do SGBD.
     */
    public function login(CredencialLogin $credencial): array
    {
        $erros = $credencial->validar();
        if ($erros !== []) {
            throw ApiException::validacao($erros);
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, senha_hash FROM usuarios WHERE email = ?'
        );
        $stmt->execute([$credencial->getEmail()]);
        $usuario = $stmt->fetch();

        $hashParaVerificar = $usuario ? (string) $usuario['senha_hash'] : self::DUMMY_BCRYPT_HASH;
        $senhaValida = password_verify($credencial->getSenha(), $hashParaVerificar);

        if (!$usuario || !$senhaValida) {
            throw ApiException::naoAutenticado('E-mail ou senha inválidos');
        }

        // Remove sessões expiradas e calcula +2h no próprio SGBD para evitar divergência de relógio
        $this->pdo->exec('DELETE FROM tokens WHERE expira_em <= NOW()');

        $token = bin2hex(random_bytes(32));
        $stmtToken = $this->pdo->prepare(
            'INSERT INTO tokens (usuario_id, token, expira_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 HOUR))'
        );
        $stmtToken->execute([(int) $usuario['id'], $token]);

        $stmtExp = $this->pdo->prepare('SELECT expira_em FROM tokens WHERE id = ?');
        $stmtExp->execute([(int) $this->pdo->lastInsertId()]);
        $expiraEm = (string) $stmtExp->fetchColumn();

        return [
            'token' => $token,
            'tipo' => 'Bearer',
            'expira_em' => $expiraEm,
        ];
    }

    /**
     * Exige autenticação e invalida imediatamente o token ativo no banco de dados.
     */
    public function logout(): void
    {
        $autenticado = $this->exigirToken();

        $stmt = $this->pdo->prepare('DELETE FROM tokens WHERE token = ?');
        $stmt->execute([$autenticado['token']]);
    }
}
