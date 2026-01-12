# OpenGRC Local Development Guide

This guide will help you set up OpenGRC for local development on macOS, Linux, or Windows (WSL).

## Prerequisites

Before you begin, ensure you have the following installed:

| Requirement | Minimum Version | Check Command |
|-------------|-----------------|---------------|
| PHP         | 8.2+            | `php -v`      |
| Composer    | 2.x             | `composer -V` |
| Node.js     | 16+             | `node -v`     |
| NPM         | 9+              | `npm -v`      |

### PHP Extensions Required

The following PHP extensions must be enabled:
- `fileinfo`
- `pdo_sqlite` (for SQLite, the default database)
- `pdo_mysql` (optional, for MySQL)
- `mbstring`
- `xml`
- `curl`
- `zip`
- `gd`
- `bcmath`
- `intl`

### Installing Prerequisites

#### macOS (using Homebrew)
```bash
brew install php@8.2 composer node
```

#### Ubuntu/Debian
```bash
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-common php8.2-sqlite3 php8.2-mysql \
    php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath \
    php8.2-intl composer nodejs npm
```

## Quick Start

For the impatient, here's the minimal setup. The installer uses **SQLite** by default, which requires no database server.

> **Note:** The `.env.example` defaults to MySQL. The installer will automatically configure SQLite for you, or you can manually update `.env` to use `DB_CONNECTION=sqlite` for local development.

```bash
# 1. Clone the repository
git clone https://github.com/LeeMangold/OpenGRC.git
cd OpenGRC

# 2. Install dependencies
composer install
npm install

# 3. Run the automated installer
php artisan opengrc:install --unattended

# 4. Start the development server
php artisan serve
```

Then open http://localhost:8000 in your browser.

**Default login credentials** (unattended install):
- Email: `admin@example.com`
- Password: `password`

For interactive installation with custom settings, omit `--unattended`:
```bash
php artisan opengrc:install
```

## Detailed Setup

### Step 1: Clone the Repository

```bash
git clone https://github.com/LeeMangold/OpenGRC.git
cd OpenGRC
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

This will also run `php artisan filament:upgrade` automatically.

### Step 3: Install Node.js Dependencies

```bash
npm install
```

### Step 4: Run the Installer

OpenGRC includes an installation wizard that handles environment setup, database creation, migrations, and initial seeding:

**Interactive mode** (recommended for custom configuration):
```bash
php artisan opengrc:install
```

**Unattended mode** (uses defaults - SQLite, admin@example.com/password):
```bash
php artisan opengrc:install --unattended
```

The installer will:
- Create/update the `.env` file
- Generate the application key
- Configure the database (SQLite by default)
- Run all migrations
- Create an admin user
- Seed default settings and permissions
- Build frontend assets
- Create the storage symlink

### Alternative: Manual Configuration

If you prefer to configure manually:

#### Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

**For local development, update `.env` to use SQLite** (simpler, no database server required):

```env
DB_CONNECTION=sqlite
# Comment out or remove these MySQL lines:
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=
```

Generate an application key:

```bash
php artisan key:generate
```

#### Using MySQL

If you prefer MySQL, keep the default `.env` settings and update with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=opengrc
DB_USERNAME=root
DB_PASSWORD=your_password
```

Then create the database in MySQL:

```sql
CREATE DATABASE opengrc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Run Migrations and Seeders

```bash
# Create SQLite file (if using SQLite)
touch database/opengrc.sqlite

# Run migrations
php artisan migrate

# Seed essential data
php artisan db:seed --class=SettingsSeeder
php artisan db:seed --class=RolePermissionSeeder

# Create admin user
php artisan opengrc:create-user admin@example.com password
```

#### Available Seeders

OpenGRC includes several seeders for compliance frameworks:

| Seeder | Description |
|--------|-------------|
| `DatabaseSeeder` | Default seeder (runs essential seeders) |
| `DemoSeeder` | Sample data for demonstration |
| `FullDemoSeeder` | Complete demo with all frameworks |
| `SP800171r2Seeder` | NIST SP 800-171 Rev 2 |
| `SP800171r3Seeder` | NIST SP 800-171 Rev 3 |
| `SP80053LowSeeder` | NIST SP 800-53 Low Baseline |
| `CMMC2L2Seeder` | CMMC Level 2 |
| `HipaaSeeder` | HIPAA Security Rule |
| `TSC2017Seeder` | SOC 2 Trust Services Criteria |

To run a specific seeder:

```bash
php artisan db:seed --class=DemoSeeder
```

### Step 7: Build Frontend Assets

For development (with hot reload):

```bash
npm run dev
```

For production build:

```bash
npm run build
```

### Step 8: Start the Development Server

```bash
php artisan serve
```

The application will be available at http://localhost:8000.

**Note:** When running `npm run dev` in a separate terminal, Vite will handle asset compilation with hot module replacement.

## Development Workflow

### Running Development Servers

You'll typically need two terminal windows:

**Terminal 1 - PHP Server:**
```bash
php artisan serve
```

**Terminal 2 - Vite (for asset hot-reload):**
```bash
npm run dev
```

### Code Quality Tools

#### Laravel Pint (Code Formatting)
```bash
vendor/bin/pint
```

#### PHPStan (Static Analysis)
```bash
vendor/bin/phpstan
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run with coverage
php artisan test --coverage
```

### Creating Filament Resources

```bash
# Create a new resource
php artisan make:filament-resource ModelName

# Generate with all options
php artisan make:filament-resource ModelName --generate --view
```

### Database Operations

```bash
# Fresh migration (drops all tables)
php artisan migrate:fresh

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback
```

### Clearing Caches

```bash
# Clear all caches
php artisan optimize:clear

# Individual cache clearing
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Default Login Credentials

After running the installer, you can log in with:

**Unattended install defaults:**
- **Email:** `admin@example.com`
- **Password:** `password`

**Interactive install:** Use the credentials you provided during installation.

To create additional users:
```bash
php artisan opengrc:create-user email@example.com yourpassword
```

## Troubleshooting

### "No application encryption key has been specified"

Run:
```bash
php artisan key:generate
```

### Database connection errors (SQLite)

Ensure the database file exists and is writable:
```bash
touch database/opengrc.sqlite
chmod 664 database/opengrc.sqlite
```

### Permission issues (Linux servers)

If deploying to a Linux server with a web server (Apache/Nginx), run:
```bash
./set_permissions
```

This script sets proper ownership and permissions for the web server.

### "Class not found" errors

Clear and regenerate autoload:
```bash
composer dump-autoload
php artisan optimize:clear
```

### Vite manifest not found

Build the frontend assets:
```bash
npm run build
```

Or ensure Vite dev server is running:
```bash
npm run dev
```

### Storage link missing

Create the symbolic link for public storage:
```bash
php artisan storage:link
```

## IDE Setup

### VS Code Extensions

Recommended extensions for OpenGRC development:
- PHP Intelephense
- Laravel Blade Snippets
- Tailwind CSS IntelliSense
- Laravel Extra Intellisense

### PHPStorm

Enable the Laravel plugin and configure:
- Set PHP interpreter to PHP 8.2+
- Configure Composer path
- Enable Laravel Pint as the code formatter

## Additional Resources

- [OpenGRC Documentation](https://docs.opengrc.com)
- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
