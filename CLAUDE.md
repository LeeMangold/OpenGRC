# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

OpenGRC is a cyber Governance, Risk, and Compliance (GRC) web application built for small businesses and teams. It provides tools for security program management, compliance framework imports, audits, and reporting.

**Core Technologies:**
- Laravel 11 (PHP 8.2+)
- Filament 3 (admin panel framework built on Livewire)
- SQLite database (default)
- Tailwind CSS for styling
- Vite for asset building

## Development Commands

### Setup & Dependencies
```bash
composer install          # Install PHP dependencies
npm install               # Install Node.js dependencies
php artisan key:generate   # Generate application key (first time setup)
```

### Development
```bash
php artisan serve         # Start development server
npm run dev              # Start Vite development server (watch assets)
npm run build            # Build production assets
```

### Testing & Quality
```bash
php artisan test         # Run PHPUnit tests
vendor/bin/phpunit       # Alternative test runner
vendor/bin/pint          # Run Laravel Pint (code formatting)
vendor/bin/phpstan       # Run PHPStan (static analysis, level 2)
```

### Database
```bash
php artisan migrate      # Run database migrations
php artisan db:seed      # Seed database with sample data
php artisan migrate:fresh --seed  # Fresh migration with seeding
```

### Filament-Specific
```bash
php artisan filament:upgrade      # Upgrade Filament components
php artisan make:filament-resource ModelName  # Create new Filament resource
```

## Architecture Overview

### Core Domain Models
The application is built around key GRC concepts:
- **Standards**: Compliance frameworks (NIST, ISO, etc.)
- **Controls**: Individual security controls within standards
- **Implementations**: How controls are actually implemented
- **Audits**: Assessment processes with audit items
- **Programs**: Organizational groupings
- **Risks**: Risk management entities
- **Vendors/Applications**: Third-party risk management

### Filament Panel Structure
OpenGRC uses two Filament panels:
- **App Panel** (`AppPanelProvider`): Main user interface
- **Admin Panel** (`AdminPanelProvider`): System administration

### Key Directories
- `app/Models/`: Eloquent models for core entities
- `app/Filament/Resources/`: Filament resource definitions (CRUD interfaces)
- `app/Filament/Pages/`: Custom Filament pages (Dashboard, Import, etc.)
- `app/Enums/`: Type-safe enumerations for status values
- `database/seeders/`: Framework imports (NIST, CMMC, etc.)
- `resources/data/`: CSV files for framework imports

### Authentication & Authorization
- Uses Laravel Sanctum for API authentication
- Spatie Laravel Permission for role-based access control
- Filament Breezy for user management
- Optional social login (OAuth2, SAML via Filament Socialite)

### Import System
- CSV-based framework imports via seeders
- IRL (Information Request List) import functionality
- Bundle system for grouping related standards/controls

### Reporting & Export
- PDF generation using DomPDF
- Audit evidence export functionality
- Custom report templates in `resources/views/reports/`

## Development Patterns

### Filament Resources
- Resources follow standard Filament patterns with form(), table(), and infolist() methods
- Use relation managers for related data (e.g., `AuditItemRelationManager`)
- Custom pages extend base Filament page classes

### Model Relationships
- Heavy use of Eloquent relationships (BelongsTo, HasMany, BelongsToMany)
- Many-to-many relationships use pivot tables (e.g., control_implementation)
- Soft deletes used for audit trails

### Enums
- Status values are type-safe PHP enums in `app/Enums/`
- Common enums: WorkflowStatus, ImplementationStatus, RiskLevel, etc.

### Localization
- Multi-language support in `lang/` directory
- Navigation and UI strings are translatable
- Enum labels support localization

## Testing

- PHPUnit configuration in `phpunit.xml`
- Tests in `tests/Feature/` and `tests/Unit/`
- Browser tests using Laravel Dusk in `tests/Browser/`
- Test database uses array drivers for speed

## Code Quality

- **Laravel Pint**: Follows Laravel coding style preset
- **PHPStan**: Static analysis at level 2
- **Composer scripts**: Automated quality checks in composer.json

## Security Notes

- Rate limiting on authentication endpoints
- CSRF protection enabled
- File upload restrictions in place
- Role-based permission system
- Session timeout middleware