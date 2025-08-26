## Developer Quick Start

# Install dependencies
composer install

# Run tests
php ./vendor/bin/phpunit tests

# Apply DB migrations (run in your DB environment)
mysql -u root -p < migrations/20250826_add_order_indexes.sql

# CI: GitHub Actions workflow is available at `.github/workflows/ci.yml` to run lint and tests on push/PR.
