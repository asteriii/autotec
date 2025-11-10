FROM php:8.2-apache

# Install MySQLi and PDO MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite (for clean URLs if needed)
RUN a2enmod rewrite

# Copy all application files to web directory (uploads excluded via .dockerignore)
COPY . /var/www/html/

# Set proper permissions for application files
RUN chown -R www-data:www-data /var/www/html

# Set ServerName to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure Apache to follow symlinks
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/symlinks.conf && \
    a2enconf symlinks

# Create startup script with volume handling
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "=== Starting Application ==="\n\
\n\
# Create upload subdirectories if they don'\''t exist\n\
# Railway volume will be mounted at /var/www/html/uploads\n\
mkdir -p /var/www/html/uploads/profile\n\
mkdir -p /var/www/html/uploads/branches\n\
mkdir -p /var/www/html/uploads/payment_receipts\n\
mkdir -p /var/www/html/uploads/qrcodes\n\
mkdir -p /var/www/html/uploads/homepage\n\
\n\
# Set permissions\n\
chmod -R 755 /var/www/html/uploads\n\
chown -R www-data:www-data /var/www/html/uploads\n\
\n\
echo "=== Upload Directories Ready ==="\n\
echo "Uploads directory:"\n\
ls -la /var/www/html/uploads/\n\
echo ""\n\
echo "Profile directory contents (first 5):"\n\
ls -la /var/www/html/uploads/profile/ 2>/dev/null | head -n 10 || echo "Empty or not accessible"\n\
echo ""\n\
echo "Branches directory contents (first 5):"\n\
ls -la /var/www/html/uploads/branches/ 2>/dev/null | head -n 10 || echo "Empty or not accessible"\n\
echo ""\n\
echo "Payment receipts directory contents (first 5):"\n\
ls -la /var/www/html/uploads/payment_receipts/ 2>/dev/null | head -n 10 || echo "Empty or not accessible"\n\
echo ""\n\
echo "Homepage directory contents (first 5):"\n\
ls -la /var/www/html/uploads/homepage/ 2>/dev/null | head -n 10 || echo "Empty or not accessible"\n\
echo "================================"\n\
\n\
# Configure Apache port\n\
export APACHE_PORT=${PORT:-80}\n\
sed -i "s/Listen 80/Listen $APACHE_PORT/g" /etc/apache2/ports.conf\n\
sed -i "s/:80/:$APACHE_PORT/g" /etc/apache2/sites-available/000-default.conf\n\
\n\
# Start Apache\n\
echo "Starting Apache on port $APACHE_PORT..."\n\
apache2-foreground' > /start.sh && chmod +x /start.sh

CMD ["/start.sh"]