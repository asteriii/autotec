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

# Create startup script with volume handling
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Handle Railway volume for uploads\n\
if [ -n "$RAILWAY_VOLUME_MOUNT_PATH" ]; then\n\
    echo "=== Railway Volume Setup ==="\n\
    echo "Volume path: $RAILWAY_VOLUME_MOUNT_PATH"\n\
    \n\
    # Create directories in volume\n\
    mkdir -p "$RAILWAY_VOLUME_MOUNT_PATH/profile"\n\
    mkdir -p "$RAILWAY_VOLUME_MOUNT_PATH/branches"\n\
    mkdir -p "$RAILWAY_VOLUME_MOUNT_PATH/payment_receipts"\n\
    \n\
    # Set permissions\n\
    chmod 755 "$RAILWAY_VOLUME_MOUNT_PATH"\n\
    chmod 755 "$RAILWAY_VOLUME_MOUNT_PATH/profile"\n\
    chmod 755 "$RAILWAY_VOLUME_MOUNT_PATH/branches"\n\
    chmod 755 "$RAILWAY_VOLUME_MOUNT_PATH/payment_receipts"\n\
    chown -R www-data:www-data "$RAILWAY_VOLUME_MOUNT_PATH"\n\
    \n\
    # Create uploads directory in web root if it does not exist\n\
    mkdir -p /var/www/html/uploads\n\
    \n\
    # Remove existing symlinks/directories and create fresh symlinks\n\
    rm -rf /var/www/html/uploads/profile\n\
    rm -rf /var/www/html/uploads/branches\n\
    rm -rf /var/www/html/uploads/payment_receipts\n\
    \n\
    # Create symlinks from web uploads to volume\n\
    ln -sf "$RAILWAY_VOLUME_MOUNT_PATH/profile" /var/www/html/uploads/profile\n\
    echo "✓ Symlink created: /var/www/html/uploads/profile -> $RAILWAY_VOLUME_MOUNT_PATH/profile"\n\
    \n\
    ln -sf "$RAILWAY_VOLUME_MOUNT_PATH/branches" /var/www/html/uploads/branches\n\
    echo "✓ Symlink created: /var/www/html/uploads/branches -> $RAILWAY_VOLUME_MOUNT_PATH/branches"\n\
    \n\
    ln -sf "$RAILWAY_VOLUME_MOUNT_PATH/payment_receipts" /var/www/html/uploads/payment_receipts\n\
    echo "✓ Symlink created: /var/www/html/uploads/payment_receipts -> $RAILWAY_VOLUME_MOUNT_PATH/payment_receipts"\n\
    \n\
    # Set ownership for symlinks and uploads directory\n\
    chown -h www-data:www-data /var/www/html/uploads/profile\n\
    chown -h www-data:www-data /var/www/html/uploads/branches\n\
    chown -h www-data:www-data /var/www/html/uploads/payment_receipts\n\
    chown www-data:www-data /var/www/html/uploads\n\
    \n\
    # Verify setup\n\
    echo "=== Verification ==="\n\
    echo "Profile symlink: $(readlink /var/www/html/uploads/profile)"\n\
    echo "Profile directory exists: $([ -d /var/www/html/uploads/profile ] && echo YES || echo NO)"\n\
    echo "Profile directory writable: $([ -w /var/www/html/uploads/profile ] && echo YES || echo NO)"\n\
    echo "Branches directory exists: $([ -d /var/www/html/uploads/branches ] && echo YES || echo NO)"\n\
    echo "Branches directory writable: $([ -w /var/www/html/uploads/branches ] && echo YES || echo NO)"\n\
    echo "Payment receipts directory exists: $([ -d /var/www/html/uploads/payment_receipts ] && echo YES || echo NO)"\n\
    echo "Payment receipts directory writable: $([ -w /var/www/html/uploads/payment_receipts ] && echo YES || echo NO)"\n\
    echo ""\n\
    echo "Uploads directory contents:"\n\
    ls -la /var/www/html/uploads/ || true\n\
    echo ""\n\
    echo "Profile directory contents:"\n\
    ls -la /var/www/html/uploads/profile/ 2>/dev/null | head -n 10 || echo "Empty or not accessible"\n\
    echo "=========================="\n\
else\n\
    echo "⚠ No Railway volume detected - using local storage"\n\
    # Create local uploads directories\n\
    mkdir -p /var/www/html/uploads/profile\n\
    mkdir -p /var/www/html/uploads/branches\n\
    mkdir -p /var/www/html/uploads/payment_receipts\n\
    chmod 755 /var/www/html/uploads/profile\n\
    chmod 755 /var/www/html/uploads/branches\n\
    chmod 755 /var/www/html/uploads/payment_receipts\n\
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