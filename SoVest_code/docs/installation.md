# SoVest Installation Guide

This document outlines the steps required to set up the SoVest application on your server.

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache or Nginx)
- SSL certificate for production environments

## Installation Steps

### 1. Database Setup

1. Create a MySQL database for SoVest

2. **Using Laravel Migrations (Recommended):**
   
   Run Laravel migrations to set up all database tables:
   
   ```bash
   php artisan migrate
   ```

   This will create all required tables including user, stock, prediction, and search-related tables.

3. **Legacy Method (Deprecated):**
   
   If needed, the legacy SQL files are still available in the `legacy` directory:
   
   ```bash
   mysql -u yourusername -p your_database_name < legacy/db_schema_update.sql
   mysql -u yourusername -p your_database_name < legacy/search_schema_update.sql
   ```

### 2. Application Files

1. Clone or download the SoVest application files to your web server's document root
2. Ensure proper permissions:

```bash
chmod 755 -R /path/to/sovest
chmod 777 -R /path/to/sovest/logs
```

### 3. Configuration

1. Update database configuration in `/includes/db_config.php`:

```php
define('DB_SERVER', 'your_database_server');
define('DB_USERNAME', 'your_database_username');
define('DB_PASSWORD', 'your_database_password');
define('DB_NAME', 'your_database_name');
```

2. Update API configuration in `/config/api_config.php`:

```php
define('ALPHA_VANTAGE_API_KEY', 'your_api_key');
define('ALPHA_VANTAGE_BASE_URL', 'https://www.alphavantage.co/query');
define('API_RATE_LIMIT', 5); // Calls per minute

// Default stocks to track
$DEFAULT_STOCKS = [
    'AAPL' => 'Apple Inc.',
    'MSFT' => 'Microsoft Corporation',
    'GOOG' => 'Alphabet Inc.',
    'AMZN' => 'Amazon.com, Inc.',
    'TSLA' => 'Tesla, Inc.'
];
```

### 4. Initial Data Setup

1. **Using Laravel Seeders (Recommended):**

   Run Laravel database seeders to populate initial data:

   ```bash
   php artisan db:seed
   ```

2. **Legacy Method (Deprecated):**

   If needed, the legacy setup script is still available in the `legacy` directory:

   ```bash
   php legacy/apply_db_schema.php
   ```

3. Verify schema installation:

   ```bash
   php verify_db_schema.php
   ```

### 5. Web Server Configuration

#### Apache

Ensure mod_rewrite is enabled and use the following .htaccess configuration:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.php [L]
</IfModule>

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"
Header set X-Frame-Options "SAMEORIGIN"
Header set Content-Security-Policy "default-src 'self' https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:;"
```

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/sovest;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
    }

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Content-Security-Policy "default-src 'self' https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:;" always;
}
```

### 6. Cron Jobs

Set up the following cron jobs for stock data updates and prediction evaluations:

```bash
# Update stock prices every hour during market hours
0 9-16 * * 1-5 php /path/to/sovest/cron/update_stock_prices.php

# Evaluate predictions daily after market close
0 17 * * 1-5 php /path/to/sovest/cron/evaluate_predictions.php
```

### 7. Testing Installation

1. Navigate to your application URL
2. Log in with the default admin account:
   - Email: admin@sovest.example.com
   - Password: Admin123!
3. Change the default password immediately

## Troubleshooting

### Common Issues

#### Database Connection Errors

- Verify database credentials in `includes/db_config.php`
- Ensure MySQL service is running
- Check database user permissions

#### API Errors

- Verify API key in `config/api_config.php`
- Check API rate limits
- Review error logs in `/logs` directory

#### Permission Issues

- Check file permissions, especially for log directories
- Ensure web server user has appropriate access

## Security Recommendations

1. Use HTTPS for all connections
2. Implement password hashing (currently using plain text)
3. Set up proper firewall rules
4. Regularly update dependencies
5. Implement brute force protection
6. Enable CSRF protection
7. Use prepared statements for database queries

## Support

For additional support, contact:
- Email: support@sovest.example.com