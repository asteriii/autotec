FROM php:8.2-apache

# Install MySQLi and PDO MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite (for clean URLs if needed)
RUN a2enmod rewrite

# Copy all application files to web directory
COPY . /var/www/html/

# Set proper permissions for application files
RUN chown -R www-data:www-data /var/www/html

# Set ServerName to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# CRITICAL FIX: Configure Apache to follow symlinks
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/symlinks.conf && \
    a2enconf symlinks

# Create startup script with direct volume handling
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Create upload subdirectories if they don'\''t exist\n\
mkdir -p /var/www/html/uploads/profile\n\
mkdir -p /var/www/html/uploads/branches\n\
mkdir -p /var/www/html/uploads/payment_receipts\n\
\n\
# Set permissions\n\
chmod -R 755 /var/www/html/uploads\n\
chown -R www-data:www-data /var/www/html/uploads\n\
\n\
echo "=== Upload Directories Ready ==="\n\
ls -la /var/www/html/uploads/\n\
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