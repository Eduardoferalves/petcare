# 🐾 PetCare API

![PHP](https://img.shields.io/badge/PHP_8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![POO](https://img.shields.io/badge/POO-Encapsulamento_%7C_Heran%C3%A7a_%7C_Polimorfismo-6C5CE7?style=for-the-badge)
![MySQL](https://img.shields.io/badge/MySQL_%2F_MariaDB-InnoDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![PDO](https://img.shields.io/badge/PDO-Prepared_Statements-00758F?style=for-the-badge&logo=databricks&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-Apache-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)

API RESTful para gestão clínica veterinária (**Tutores** e **Pets**), desenvolvida em **PHP 8+ nativo orientado a objetos (POO)**, persistência relacional com **PDO + MySQL (`InnoDB`)** e autenticação stateful via **Bearer Token**.

---

## 🚀 Guia Rápido de Configuração

1. **Instale o XAMPP** (com suporte a PHP 8.0+ e MySQL/MariaDB).
2. **Clone o repositório dentro da pasta `htdocs`** do XAMPP:
   ```bash
   cd C:\xampp\htdocs
   git clone <url-do-repositorio> petcare
   ```
3. **Inicie os serviços Apache e MySQL** no painel de controle do **XAMPP Control Panel**.
4. **Acesse o phpMyAdmin** em [`http://localhost/phpmyadmin`](http://localhost/phpmyadmin):
   - Clique na aba **SQL** (no menu superior).
   - Copie e cole todo o conteúdo do arquivo [`banco.sql`](banco.sql) e clique em **Executar** *(o script já cria o banco `petcare`, as 4 tabelas e os dados iniciais automaticamente)*.
5. **Acesse e teste a API** em `http://localhost/petcare/`:
   - Para testar todas as rotas (`GET`, `POST`, `PUT`, `DELETE`), abra o arquivo [`requisicoes.http`](requisicoes.http) no VS Code com a extensão **REST Client** instalada.

---

## 🔐 Credenciais de Teste (`POST /login`)

Contas pré-cadastradas no [`banco.sql`](banco.sql) (hashes gerados com `password_hash('123456', PASSWORD_DEFAULT)`):

| Perfil | E-mail | Senha |
| :--- | :--- | :--- |
| **Administrador PetCare** | `admin@petcare.com.br` | `123456` |
| **Recepção Clínica PetCare** | `recepcao@petcare.com.br` | `123456` |

---

## 🏛️ Arquitetura Orientada a Objetos (POO)

O projeto adota o princípio de **um arquivo por classe/interface** dentro do diretório [`src/`](src/), aplicando todos os pilares de POO em PHP 8+:

```text
petcare/
├── .htaccess                     # Reescrita de URLs e repasse do header Authorization
├── banco.sql                     # Criação idempotente do banco petcare, 4 tabelas e seeds
├── index.php                     # Front Controller / Roteador HTTP
├── requisicoes.http              # Suíte de testes automatizados (REST Client)
├── README.md                     # Guia rápido e documentação principal
├── docs/
│   └── MER.md                    # Modelagem de Dados (MER), Diagrama Mermaid e Mapeamento Relacional
└── src/
    ├── Serializavel.php          # Interface de contrato para serialização JSON (toArray)
    ├── Validavel.php             # Interface de contrato para validação de domínio (validar)
    ├── EntidadeBase.php          # Classe abstrata base (herança, protected $id, múltiplas interfaces)
    ├── Tutor.php                 # Entidade de domínio Tutor (extends EntidadeBase)
    ├── Pet.php                   # Entidade de domínio Pet (extends EntidadeBase)
    ├── CredencialLogin.php       # Objeto de valor para autenticação (implements Validavel)
    ├── RepositorioBase.php       # Classe abstrata base de persistência (protected PDO $pdo)
    ├── TutorRepositorio.php      # Persistência CRUD de Tutores (extends RepositorioBase)
    ├── PetRepositorio.php        # Persistência CRUD de Pets (extends RepositorioBase)
    ├── AutenticacaoServico.php   # Gestão de sessão Bearer Token (extends RepositorioBase)
    ├── BancoDados.php            # Classe final de conexão PDO (final class)
    └── Http.php                  # Classe final de I/O JSON e polimorfismo HTTP (final class)
```

---

## 🗄️ Modelagem do Banco de Dados (MER) e Integridade

A documentação completa da modelagem conceitual, diagrama Entidade-Relacionamento visual, mapeamento relacional em 8 passos (Elmasri & Navathe), dicionário de dados físico e formas normais (`1FN`, `2FN`, `3FN`) está disponível em:

👉 **[`docs/MER.md` — Especificação Formal do Banco de Dados e MER](docs/MER.md)**

### Regra de Exclusão de Tutores (`ON DELETE RESTRICT`)

Na chave estrangeira `pets.tutor_id -> tutores.id`, adotou-se a política **`ON DELETE RESTRICT`**:
- **Motivo:** Proteger o prontuário clínico veterinário contra exclusões acidentais em cascata.
- **Comportamento na API:** Tentar excluir um tutor que possui pets vinculados (`DELETE /tutores/{id}`, ex.: `id = 1`) é bloqueado pelo MySQL (erro `1451`) e retorna **`409 Conflict`**:
  ```json
  {
      "detail": "Não é possível excluir tutor que possui pets vinculados"
  }
  ```
- Tutores sem pets vinculados (ex.: `id = 6`) são excluídos normalmente com status **`204 No Content`**.

---

## 📡 Endpoints da API

| Método | Rota | Autenticação | Sucesso | Erros Tratados |
| :--- | :--- | :---: | :--- | :--- |
| `POST` | `/login` | Pública | `200` (`token`, `tipo`, `expira_em`) | `400`, `401`, `422` |
| `POST` | `/logout` | `Bearer Token` | `204` *(revoga token)* | `401` |
| `GET` | `/tutores` | Pública | `200` (`total`, `tutores`) | — |
| `GET` | `/tutores/{id}` | Pública | `200` *(dados do tutor)* | `404` |
| `GET` | `/tutores/{id}/pets` | Pública | `200` *(pets do tutor)* | `404` |
| `POST` | `/tutores` | `Bearer Token` | `201` *(tutor criado)* | `400`, `401`, `409`, `422` |
| `PUT` | `/tutores/{id}` | `Bearer Token` | `200` *(tutor atualizado)* | `400`, `401`, `404`, `409`, `422` |
| `DELETE` | `/tutores/{id}` | `Bearer Token` | `204` *(sem corpo)* | `401`, `404`, `409` |
| `GET` | `/pets` | Pública | `200` *(aceita `?especie=`)* | `422` *(espécie inválida)* |
| `GET` | `/pets/{id}` | Pública | `200` *(inclui `tutor_nome` via `JOIN`)* | `404` |
| `POST` | `/pets` | `Bearer Token` | `201` *(pet criado)* | `400`, `401`, `422` |
| `PUT` | `/pets/{id}` | `Bearer Token` | `200` *(pet atualizado)* | `400`, `401`, `404`, `422` |
| `DELETE` | `/pets/{id}` | `Bearer Token` | `204` *(sem corpo)* | `401`, `404` |

---

## 👥 Autores

- **Eduardo Fernandes Alves**
- **Rafael Torres Teixeira**
