# Product Inventory API

A Laravel 11 REST API for managing products, categories, and suppliers. Product routes use Laravel Sanctum bearer tokens; products include category and many-to-many supplier relationships, validated filters, pagination, and soft deletion.

## Requirements

- PHP 8.2 or newer with OpenSSL, PDO SQLite (or your configured database driver), Mbstring, Fileinfo, and Tokenizer enabled
- Composer 2

## Setup

```powershell
composer install --no-security-blocking
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite -ItemType File
```

Configure `.env` for SQLite:

```dotenv
DB_CONNECTION=sqlite
```

Then run migrations and seed sample inventory:

```bash
php artisan migrate --seed
php artisan serve
```

The API is served at `http://127.0.0.1:8000/api`. Run feature tests with:

```bash
php artisan test
```

## Authentication

Register at `POST /api/auth/register` or sign in at `POST /api/auth/login`. Both return a Sanctum token. Send it on protected requests as `Authorization: Bearer <token>`. Revoke the current token at `POST /api/auth/logout`.

## Product endpoints

All product endpoints require authentication.

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/api/products` | Paginated product list |
| POST | `/api/products` | Create a product |
| GET | `/api/products/{id}` | Show a product |
| PUT/PATCH | `/api/products/{id}` | Update a product |
| DELETE | `/api/products/{id}` | Soft-delete a product |

List filters can be combined: `category_id`, `min_price`, `max_price`, `stock_level` (`out`, `low`, or `in_stock`), and `per_page` (1-100). Example: `/api/products?category_id=1&min_price=10&max_price=50&stock_level=low&per_page=20`.

Create/update payloads accept `category_id`, `sku`, `name`, `description`, `price`, `stock_quantity`, `reorder_level`, `is_active`, and `supplier_ids` (an array of existing supplier IDs). Product responses are formatted with API Resources and include category, suppliers, and computed `stock_status`.

## GitHub submission

Create an empty GitHub repository, then from this directory run:

```bash
git init
git add .
git commit -m "Build product inventory REST API"
git branch -M main
git remote add origin https://github.com/<your-account>/<repository>.git
git push -u origin main
```

This project intentionally targets Laravel 11 as requested. Laravel 11 is no longer in its security-fix support window, and the installed framework version is currently flagged by Composer's security advisory policy. The `--no-security-blocking` install option is included only to satisfy the requested version; upgrade to a supported Laravel release before using this API in production.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# inventory-api
# inventory-api
