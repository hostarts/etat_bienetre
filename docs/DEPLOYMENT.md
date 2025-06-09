# Deployment Guide - Bienetre Pharma

## Production Deployment

### 1. Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache 2.4+ with mod_rewrite
- SSL certificate (recommended)

### 2. File Permissions
```bash
# Set proper permissions
chmod -R 755 app/
chmod -R 755 public/
chmod -R 777 storage/
chmod 600 .env
```

### 3. Environment Configuration
```bash
# Copy and configure environment
cp .env.example .env

# Update .env for production:
DEBUG=false
APP_ENV=production
APP_URL=https://your-domain.com
```

### 4. Database Setup
```bash
mysql -u root -p < database.sql
```

### 5. Apache Virtual Host
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/bienetre-pharma/public
    
    <Directory /path/to/bienetre-pharma/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Redirect to HTTPS
    RewriteEngine on
    RewriteCond %{SERVER_NAME} =your-domain.com
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /path/to/bienetre-pharma/public
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /path/to/bienetre-pharma/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 6. Security Checklist
- [ ] Set DEBUG=false in .env
- [ ] Configure strong database passwords
- [ ] Enable HTTPS with valid SSL certificate
- [ ] Set proper file permissions
- [ ] Configure firewall rules
- [ ] Enable automatic backups
- [ ] Set up log rotation

### 7. Performance Optimization
- Enable Apache mod_deflate for compression
- Configure browser caching headers
- Optimize database queries
- Consider using a CDN for static assets

### 8. Monitoring
- Set up log monitoring for errors
- Monitor database performance
- Track application metrics
- Configure alerts for critical issues
