chown 1000.1000 . -R
find /opt/www/MGspa -type d -exec chmod 775 {} \;
find /opt/www/MGspa -type f -exec chmod 664 {} \;
chmod a+w /opt/www/MGspa/laravel/storage -R
chmod a+w /opt/www/NFePHP -R
chmod +x /opt/www/MGspa/shell
chmod +x /opt/www/MGspa/start
chmod +x /opt/www/MGspa/stop
chmod +x /opt/www/MGspa/copiar-imagens
