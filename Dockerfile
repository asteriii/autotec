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

# Create startup script with volume handling
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Handle Railway volume for uploads\n\
if [ -n "$RAILWAY_VOLUME_MOUNT_PATH" ]; then\n\
    echo "=== Railway Volume Setup ==="\n\
    echo "Volume path: $RAILWAY_VOLUME_MOUNT_PATH"\n\
    \n\
    # Create profile directory in volume\n\
    mkdir -p "$RAILWAY_VOLUME_MOUNT_PATH/profile"\n\
    \n\
    # Set permissions\n\
    chmod 755 "$RAILWAY_VOLUME_MOUNT_PATH"\n\
    chmod 755 "$RAILWAY_VOLUME_MOUNT_PATH/profile"\n\
    chown -R www-data:www-data "$RAILWAY_VOLUME_MOUNT_PATH"\n\
    \n\
    # Create uploads directory in web root if it does not exist\n\
    mkdir -p /var/www/html/uploads\n\
    \n\
    # Create symlink from web uploads to volume\n\
    if [ ! -L "/var/www/html/uploads/profile" ]; then\n\
        ln -sf "$RAILWAY_VOLUME_MOUNT_PATH/profile" /var/www/html/uploads/profile\n\
        echo "✓ Symlink created: /var/www/html/uploads/profile -> $RAILWAY_VOLUME_MOUNT_PATH/profile"\n\
    else\n\
        echo "✓ Symlink already exists"\n\
    fi\n\
    \n\
    # Verify setup\n\
    echo "Upload directory exists: $([ -d /var/www/html/uploads/profile ] && echo YES || echo NO)"\n\
    echo "Upload directory writable: $([ -w /var/www/html/uploads/profile ] && echo YES || echo NO)"\n\
    ls -la /var/www/html/uploads/ || true\n\
    echo "=========================="\n\
else\n\
    echo "⚠ No Railway volume detected - using local storage"\n\
    # Create local uploads directory\n\
    mkdir -p /var/www/html/uploads/profile\n\
    chmod 755 /var/www/html/uploads/profile\n\
    chown -R www-data:www-data /var/www/html/uploads\n\
fi\n\
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