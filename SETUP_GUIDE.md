# Hướng dẫn Cài đặt & Triển khai API

## 1. Yêu cầu hệ thống

- PHP >= 8.1
- Composer
- MySQL >= 8.0 hoặc PostgreSQL >= 13
- Redis (optional, cho cache và queue)
- Node.js >= 16 (cho build assets)

## 2. Cài đặt

### Bước 1: Clone và cài đặt dependencies

```bash
# Clone repository
git clone <repository-url>
cd <project-folder>

# Cài đặt PHP dependencies
composer install

# Cài đặt Node dependencies
npm install
```

### Bước 2: Cấu hình môi trường

```bash
# Copy file .env
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret
```

### Bước 3: Cấu hình database trong `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Bước 4: Chạy migrations

```bash
# Chạy tất cả migrations
php artisan migrate

# Hoặc migrate fresh (xóa toàn bộ data cũ)
php artisan migrate:fresh

# Seed dữ liệu mẫu (optional)
php artisan db:seed
```

### Bước 5: Cấu hình storage

```bash
# Tạo symbolic link cho storage
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
```

### Bước 6: Chạy ứng dụng

```bash
# Development server
php artisan serve

# Hoặc với port tùy chỉnh
php artisan serve --port=8080

# Queue worker (nếu sử dụng)
php artisan queue:work

# Schedule (nếu có cron jobs)
php artisan schedule:work
```

## 3. Cấu hình bổ sung

### Email Configuration

Cấu hình SMTP trong `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### SMS/OTP Configuration

```env
# Twilio hoặc SMS provider khác
SMS_PROVIDER=twilio
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=+1234567890
```

### Payment Gateway

```env
# VNPay
VNPAY_TMN_CODE=your_tmn_code
VNPAY_HASH_SECRET=your_hash_secret
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html

# Momo
MOMO_PARTNER_CODE=your_partner_code
MOMO_ACCESS_KEY=your_access_key
MOMO_SECRET_KEY=your_secret_key
```

### File Storage (S3/Cloud)

```env
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=your_bucket_name
AWS_URL=https://your-bucket.s3.amazonaws.com
```

## 4. Testing

### Chạy tests

```bash
# Chạy tất cả tests
php artisan test

# Chạy test cụ thể
php artisan test --filter=AuthTest

# Với coverage
php artisan test --coverage
```

### Test API với Postman

1. Import collection từ file `postman_collection.json`
2. Cấu hình environment variables:
   - `base_url`: http://localhost:8000/api
   - `token`: Bearer token sau khi login

## 5. Deployment

### Production Checklist

```bash
# 1. Optimize autoloader
composer install --optimize-autoloader --no-dev

# 2. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Optimize
php artisan optimize

# 4. Set APP_ENV=production trong .env
APP_ENV=production
APP_DEBUG=false
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Supervisor Configuration (Queue Worker)

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600
```

## 6. Bảo mật

### CORS Configuration

Cấu hình trong `config/cors.php`:

```php
'allowed_origins' => [
    'https://yourdomain.com',
    'https://app.yourdomain.com',
],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

### Rate Limiting

Đã cấu hình trong `routes/api.php`:
- Auth endpoints: 5 requests/phút
- API endpoints: 60 requests/phút

Tùy chỉnh trong `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'api' => [
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

### SSL/HTTPS

```bash
# Sử dụng Let's Encrypt
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

## 7. Monitoring & Logging

### Log Configuration

Logs được lưu tại `storage/logs/laravel.log`

Cấu hình trong `.env`:

```env
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### Error Tracking

Tích hợp Sentry (optional):

```bash
composer require sentry/sentry-laravel
```

```env
SENTRY_LARAVEL_DSN=your_sentry_dsn
```

## 8. Backup

### Database Backup

```bash
# Manual backup
php artisan backup:run

# Hoặc sử dụng cron
0 2 * * * cd /var/www/html && php artisan backup:run
```

### Automated Backup Script

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
DB_NAME="your_database"

# Backup database
mysqldump -u root -p$DB_PASSWORD $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/storage

# Delete old backups (older than 30 days)
find $BACKUP_DIR -type f -mtime +30 -delete
```

## 9. Troubleshooting

### Common Issues

**1. Permission denied errors:**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**2. JWT token errors:**
```bash
php artisan jwt:secret
php artisan config:clear
```

**3. Database connection errors:**
- Kiểm tra credentials trong `.env`
- Kiểm tra MySQL service: `sudo systemctl status mysql`

**4. Queue not processing:**
```bash
php artisan queue:restart
php artisan queue:work --tries=3
```

## 10. API Documentation

Xem file `API_DOCUMENTATION.md` để biết chi tiết về các endpoints.

### Swagger/OpenAPI (Optional)

```bash
# Generate API documentation
php artisan l5-swagger:generate

# Access at: http://localhost:8000/api/documentation
```

## 11. Development Tools

### Laravel Telescope (Development)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access at: http://localhost:8000/telescope

### Laravel Debugbar (Development)

```bash
composer require barryvdh/laravel-debugbar --dev
```

## 12. Performance Optimization

### Caching

```bash
# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Clear all cache
php artisan optimize:clear
```

### Database Optimization

```bash
# Index optimization
php artisan db:show
php artisan db:table users --show-indexes

# Query optimization
# Sử dụng eager loading để tránh N+1 queries
```

### Redis Configuration

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 13. Liên hệ & Hỗ trợ

- Email: support@yourdomain.com
- Documentation: https://docs.yourdomain.com
- Issue Tracker: https://github.com/yourorg/yourrepo/issues
