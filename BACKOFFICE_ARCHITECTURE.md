# Backoffice Architecture

## Objetivo

Esta base cria um painel administrativo moderno em paralelo ao admin legado, preservando compatibilidade com o sistema atual enquanto introduz:

- autenticação por email e senha com `password_hash`
- sessão segura com timeout e regeneração
- CSRF em ações sensíveis
- controle de permissões por módulo
- rate limit de login
- logs de auditoria
- dashboard com DataTables e Chart.js

## Entrada do sistema

- `backoffice/index.php`: login
- `backoffice/dashboard.php`: dashboard principal
- `backoffice/users.php`: CRUD de usuários
- `backoffice/permissions.php`: matriz de permissões
- `backoffice/logs.php`: auditoria
- `backoffice/settings.php`: configurações
- `backoffice/profile.php`: perfil do usuário
- `backoffice/api/metrics.php`: API interna REST para dashboard
- `backoffice/install.php`: resumo da instalação inicial

## Estrutura

- `app/Core`: base de controller, view e conexão
- `app/Http/Controllers/Admin`: controllers do backoffice
- `app/Http/Middleware`: autenticação, autorização e CSRF
- `app/Models`: acesso aos dados administrativos
- `app/Services`: autenticação, sessão, logs, rate limit, dashboard
- `app/Views/admin`: layout e telas
- `assets/admin`: CSS e JS do painel
- `bootstrap/admin.php`: bootstrap central do novo painel

## Segurança

- sessão com `httponly`, `samesite`, `strict_mode`
- timeout via `ADMIN_SESSION_TIMEOUT`
- `session_regenerate_id(true)` no login
- CSRF token validado em `POST`
- rate limit persistido em `admin_login_attempts`
- permissão por módulo em `admin_permissions` e `admin_user_permissions`
- auditoria em `admin_audit_logs`

## Banco

As tabelas do novo painel são criadas automaticamente pelo `AdminInstaller`:

- `admin_users`
- `admin_permissions`
- `admin_user_permissions`
- `admin_audit_logs`
- `admin_settings`
- `admin_login_attempts`

## Primeiro acesso

- email: `ADMIN_DEFAULT_EMAIL`
- senha inicial: `ADMIN_ACCESS_CODE`

Troque a senha no primeiro login.
