chown 1000.1000 . -R
find /opt/www/MGspa -type d -exec chmod 775 {} \;
find /opt/www/MGspa -type f -exec chmod 664 {} \;
chmod a+w /opt/www/MGspa/laravel/storage -R
chmod a+w /opt/www/NFePHP -R
chmod +x /opt/www/MGspa/shell
chmod +x /opt/www/MGspa/start
<<<<<<< HEAD
chmod +x /opt/www/MGspa/stop
=======
chmox +x /opt/www/MGspa/stop
>>>>>>> 6077b113759c9a6d0b644b332086a0952ec0816a
