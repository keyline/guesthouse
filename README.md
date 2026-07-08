# Hotel Chain Manager

Laravel application foundation for a PostgreSQL-backed hotel booking and management platform for multi-property hotel owners.

## Stack

- Laravel 12
- PHP 8.2+ via XAMPP compatibility
- PostgreSQL via `pdo_pgsql`
- PostgreSQL application database, with local file/sync session, cache, and queue settings until PostgreSQL is running

## Local Setup

Install PHP dependencies:

```bash
composer install
```

Copy the environment file and generate an app key if needed:

```bash
cp .env.example .env
php artisan key:generate
```

Create a PostgreSQL database named `guesthouse001`, then update `.env` if your local username, password, host, or port differ:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=guesthouse001
DB_USERNAME=postgres
DB_PASSWORD=
```

Run the base migrations after PostgreSQL is running:

```bash
php artisan migrate
```

Start the app:

```bash
php artisan serve
```

## Verification

Check the active Laravel configuration:

```bash
php artisan about
```

Run tests:

```bash
php artisan test
```
