

# MGsis
http://localhost:82/MGsis/
Mgsis/protected/.env.php

# MGLara
http://localhost:83/MGLara/
MGLara/.env
vi /etc/php/7.1/cli/php.ini
extension=memcached.so
composer install

# MGspa
http://localhost:91/ - API
http://localhost:92/ - PWA
http://localhost:8080/ - PWA Live
./shell

cd laravel/
composer install
MGspa/laravel/.env

cd ../quasar-v1/
npm install
quasar dev --mode pwa
