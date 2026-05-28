# Titanium Lunch

Sistema de controle de almoço da **Titanium Telecom** — PHP 8.3 + MySQL 8.x.

## Requisitos

- PHP 8.3+ (extensões: `pdo_mysql`, `session`, `json`)
- MySQL 8.x ou MariaDB 10.4+
- Apache/Nginx ou `php -S` para desenvolvimento

## Instalação

1. Copie a pasta `titanium-lunch` para o servidor (ex.: `C:\xampp\htdocs\titanium-lunch`).

2. **Banco:** copie `config/database.local.php.example` → `config/database.local.php` e configure `DB_PASS`.

3. **App:** copie `config/app.local.php.example` → `config/app.local.php` e defina pelo menos:
   ```php
   define('KIOSK_PIN', '2024');  // ou '' para desativar
   ```

4. Crie o banco:
   ```bash
   mysql -u root -p < setup/install.sql
   php setup/apply-migrations.php
   ```

5. **Document root (recomendado):** aponte o servidor para a pasta `public/`  
   Ex.: `http://localhost/titanium-lunch/public/`  
   A raiz antiga (`index.php` na raiz) continua funcionando para compatibilidade.

6. Acesse:
   - Marcação: `index.php?page=home`
   - Quiosque: `kiosk.php` (PIN em `app.local.php`)
   - Admin: `/admin` — `admin` / `titanium2024` (troca obrigatória no 1º acesso)
   - Relatório: exige login admin

## Desenvolvimento local

Na raiz do projeto (modo legado):

```bash
php -S localhost:8080 -t .
```

Com pasta `public/` (recomendado):

```bash
php -S localhost:8080 -t public public/router.php
```

## Recursos

| Recurso | Descrição |
|---------|-----------|
| Quiosque | PIN do aparelho, **PIN pessoal (4 dígitos)** por colaborador, auto-bloqueio, botão **Bloquear** |
| Marcação | Confirmação “não almoçou”, desfazer (30 s), origem (`home`/`kiosk`) |
| Relatório | Filtros, pendentes hoje, cards no mobile, CSV (admin) |
| Admin | CRUD, Excel, histórico de importações, troca de senha |
| Segurança | Rate limit login/API, CSRF, pastas sensíveis bloqueadas |

## Configuração (`config/app.local.php`)

| Constante | Padrão | Uso |
|-----------|--------|-----|
| `KIOSK_PIN` | *(vazio)* | PIN do quiosque |
| `KIOSK_IDLE_MINUTES` | `15` | Bloqueia após inatividade (0 = desliga) |
| `MARKING_MODE` | `open` | `kiosk_only` = só quiosque marca |
| `DAY_LOCK_TIME` | `23:59` | Horário que encerra o dia |
| `BLOCK_WEEKENDS` | `false` | Bloqueia sábado/domingo |
| `UNDO_SECONDS` | `30` | Janela do desfazer |
| `KIOSK_REQUIRE_EMPLOYEE_PIN` | `true` | Exige PIN de 4 dígitos do colaborador no quiosque |

### PIN dos colaboradores

1. No **admin**, cadastre o PIN de 4 dígitos em cada funcionário ou use **Gerar PINs** (CSV para distribuir).
2. No **quiosque**, ao marcar almoço, o colaborador digita o próprio PIN no teclado numérico.

## Estrutura

| Caminho | Função |
|---------|--------|
| `public/` | Document root recomendado |
| `index.php` / `kiosk.php` | Entrada (legado) |
| `src/controllers/` | `LunchController`, `ReportController`, `AdminController` |
| `src/helpers/` | Auth, CSRF, PIN, quiosque, importação |
| `api/` | Endpoints AJAX |
| `admin/index.php` | Roteador fino → `AdminController` |
| `setup/` | SQL e migrations (somente CLI) |

## Testes

```bash
composer require --dev phpunit/phpunit
vendor/bin/phpunit
```

Ou, com PHPUnit global: `phpunit` na pasta do projeto.

## Segurança

- Prepared statements (PDO)
- CSRF em POST
- Relatório e CSV apenas para admin logado
- Rate limit no toggle e no login admin
- Sessão admin com timeout de 2 h
- Headers de segurança (CSP, X-Frame-Options, etc.)
- `config/`, `setup/`, `src/`, `storage/`, `data/` negados via `.htaccess` (Apache)

---

© Titanium Telecom
