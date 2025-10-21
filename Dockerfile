FROM php:8.2-apache

# Install MySQLi and PDO MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite (for clean URLs if needed)
RUN a2enmod rewrite

# Copy all application files to web directory
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Set ServerName to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Create startup script that configures Apache to use Railway's PORT
COPY <<EOF /start.sh
#!/bin/bash
set -e

# Use Railway's PORT environment variable (defaults to 80 if not set)
export APACHE_PORT=\${PORT:-80}

# Update Apache configuration
sed -i "s/Listen 80/Listen \$APACHE_PORT/g" /etc/apache2/ports.conf
sed -i "s/:80/:\$APACHE_PORT/g" /etc/apache2/sites-available/000-default.conf

# Start Apache
apache2-foreground
EOF

RUN chmod +x /start.sh

CMD ["/start.sh"]