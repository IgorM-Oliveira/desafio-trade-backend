from textwrap import dedent

readme_content = dedent("""
# Backend — Campeonato Eliminatório (Laravel 12 + PostgreSQL + Sail)

API REST para organizar e **simular um campeonato eliminatório** (quartas → semis → 3º lugar → final).
Autenticação com **Laravel Sanctum**, **Docker/Sail**, testes (unit/feature) e coleção **Postman** pronta.

---

## Visão geral
- **Stack**: Laravel 12, PHP 8.3, PostgreSQL 16, Docker + Laravel Sail, Sanctum.
- **Domínio**:
  - 8 times → 4 jogos de **quartas (QF)** → 2 **semis (SF)** → **3º lugar (THIRD)** + **final (FINAL)**.
  - **Desempate por jogo**: gols >; se empate:
    1) maior **pontuação acumulada** do torneio *(gols pró – gols contra)* até antes do jogo;
    2) persistindo empate: **menor `registered_order`** (ordem de inscrição).
  - **Standings** finais (1º..4º) + pontos acumulados.

---

## Pré-requisitos
- Docker + Docker Compose
- Composer
- Postman

---

## Configuração & Execução

### 1) Dependências e `.env`
```bash
composer install

cp .env.example .env
php artisan key:generate
