<?php

require_once __DIR__ . '/RepositorioBase.php';
require_once __DIR__ . '/CredencialLogin.php';
require_once __DIR__ . '/Http.php';

/**
 * Classe AutenticacaoServico
 * Gerencia a autenticação por Bearer Token (login, verificação em tempo real e logout).
 */
class AutenticacaoServico extends RepositorioBase
{
    public function __construct(
        PDO $pdo,
        private Http $http
    ) {
        parent::__construct($pdo);
    }

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
     * Encerra com 401 caso o token não seja informado, seja malformado, não exista ou esteja expirado.
     */
    public function exigirToken(): array
    {
        $cabecalho = $this->obterCabecalhoAuthorization();
        if ($cabecalho === null) {
            $this->http->responder(['detail' => 'Token não informado'], 401);
        }

        $token = $this->extrairTokenDeCabecalho($cabecalho);
        if ($token === null) {
            $this->http->responder(['detail' => 'Token inválido ou expirado'], 401);
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
            $this->http->responder(['detail' => 'Token inválido ou expirado'], 401);
        }

        return $registro;
    }

    /**
     * Realiza o login do operador, gera o token CSPRNG de 64 caracteres e grava na tabela tokens.
     */
    public function login(CredencialLogin $credencial): array
    {
        $this->http->validarOuResponderErro($credencial);

        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, senha_hash FROM usuarios WHERE email = ?'
        );
        $stmt->execute([$credencial->getEmail()]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($credencial->getSenha(), $usuario['senha_hash'])) {
            $this->http->responder(['detail' => 'E-mail ou senha inválidos'], 401);
        }

        $token = bin2hex(random_bytes(32));
        $expiraEm = date('Y-m-d H:i:s', strtotime('+2 hours'));

        $stmtToken = $this->pdo->prepare(
            'INSERT INTO tokens (usuario_id, token, expira_em) VALUES (?, ?, ?)'
        );
        $stmtToken->execute([(int) $usuario['id'], $token, $expiraEm]);

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
