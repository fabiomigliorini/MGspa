# LARAVEL

## Inicializar Projeto
    cd /var/www/MGspa
    composer create-project --prefer-dist "laravel/laravel" mg-laravel-5.5-a "5.5.*"

## Dar permissões nas pastas publicas
    chmod a+w mg-laravel-5.5-a/storage/ -R
    chmod a+w mg-laravel-5.5-a/bootstrap/cache/ -R

## Criar variaveis de Ambiente
    cp mg-laravel-5.5-a/.env.example mg-laravel-5.5-a/.env

## Gerar Chave da Aplicação
    php artisan key:generate
    vi .env
    Trocar APP_KEY pela chave gerada

## Configuracoes do Ambiente
    APP_NAME=MGspa
    APP_ENV=local
    APP_KEY=base64:JUf4SJL/X2tCHF5G/qkCGILcxEIoYo5PX52aCULvmL8=
    APP_DEBUG=true
    APP_LOG=daily
    APP_LOG_LEVEL=debug
    APP_URL=http://api.escmig98.teste/

    DB_CONNECTION=pgsql
    DB_HOST=localhost
    DB_PORT=5432
    DB_DATABASE=mgsis
    DB_USERNAME=mgsis
    DB_PASSWORD=mgsis
    DB_SCHEMA=mgsis

    BROADCAST_DRIVER=log
    CACHE_DRIVER=file
    SESSION_DRIVER=file
    SESSION_LIFETIME=120
    QUEUE_DRIVER=sync

    REDIS_HOST=127.0.0.1
    REDIS_PASSWORD=null
    REDIS_PORT=6379

    MAIL_DRIVER=smtp
    MAIL_HOST=smtp.mailtrap.io
    MAIL_PORT=2525
    MAIL_USERNAME=null
    MAIL_PASSWORD=null
    MAIL_ENCRYPTION=null

    PUSHER_APP_ID=
    PUSHER_APP_KEY=
    PUSHER_APP_SECRET=
    PUSHER_APP_CLUSTER=mt1

    SESSION_DRIVER=memcached

## Congiguar /var/www/MGspa/mg-laravel-5.5-a/config/app.php
    'timezone' => 'America/Cuiaba',
    'locale' => 'pt_BR',

## Configurar /var/www/MGspa/mg-laravel-5.5-a/config/database.php
    'default' => env('DB_CONNECTION', 'pgsql'),
    No array 'connections' comentar tudo que não seja a conexão pgsql

    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => env('DB_SCHEMA', 'public'),
            'sslmode' => 'prefer',
        ],
    ],


## Configurar DNS e Apache
    rm api
    ln -s mg-laravel-5.5-a/public/ api

EM /etc/apache2/sites-available/api.conf:

    <VirtualHost *:80>
      ServerName api.escmig98.teste
      DocumentRoot "/var/www/MGspa/api"
      <Directory "/var/www/MGspa/api">
        AllowOverride all
      </Directory>
    </VirtualHost>

Habilitar Site

    sudo a2ensite api
    sudo service apache2 restart

CONFIGURAR api.escmig98.teste no DNS para o IP Da máquina, EX 192.168.1.198 nos Mikrotiks

## Instalar Dependência JWT

    cd /var/www/MGspa/mg-laravel-5.5-a
    composer require tymon/jwt-auth "dev-develop"

em config/app.php

    'aliases' => [
        ...
        'JWTAuth' => Tymon\JWTAuth\Facades\JWTAuth::class,
        'JWTFactory' => Tymon\JWTAuth\Facades\JWTFactory::class,

em /var/www/MGspa/mg-laravel-5.5-a/app/Http/Kernel.php

    protected $routeMiddleware = [
        ...
        'jwt-refreh' => \Tymon\JWTAuth\Middleware\RefreshToken::class,
        'jwt-auth' => \App\Http\Middleware\JWT::class,
    ];

Copiar Middleware JWT

    cd /var/www/MGspa/mg-laravel-5.5-a
    cp ../mg-laravel-5.5/app/Http/Middleware/JWT.php app/Http/Middleware/JWT.php

Publicar JWT

    cd /var/www/MGspa/mg-laravel-5.5-a
    php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
    php artisan jwt:secret

### Configurar Autenticação

    cd /var/www/MGspa/mg-laravel-5.5-a
    php artisan make:auth
    mv app/Http/Controllers/Auth/LoginController.php app/Http/Controllers/Auth/LoginController.php.original
    cp ../mg-laravel-5.5/app/Http/Controllers/Auth/LoginController.php app/Http/Controllers/Auth/LoginController.php
    mv routes/api.php routes/api.php.original
    cp ../mg-laravel-5.5/routes/api.php routes/api.php



em config/auth.php

    'providers' => [
        'users' => [
            ...
            //'model' => App\User::class,
            'model' => \Usuario\Usuario::class,
        ],

    'model' => \Mg\Usuario\Usuario::class,


## Configurar Alias do Psr-4

em /var/www/MGspa/mg-laravel-5.5-a/composer.json
    "autoload": {
        ...
        "psr-4": {
            ...
            "Mg\\": "app/Mg"
        }
    },


## Instalar Dependência CORS

    cd /var/www/MGspa/mg-laravel-5.5-a
    composer require barryvdh/laravel-cors

em /var/www/MGspa/mg-laravel-5.5-a/app/Http/Kernel.php

    protected $middlewareGroups = [
        'api' => [
            ...
            'cors' => \Barryvdh\Cors\HandleCors::class,
            ],
        ];

    protected $routeMiddleware = [
        ...
        'cors' => \Barryvdh\Cors\HandleCors::class,
    ];


Publicar CORS

    cd /var/www/MGspa/mg-laravel-5.5-a
    php artisan vendor:publish --provider="Barryvdh\Cors\ServiceProvider"


em /var/www/MGspa/mg-laravel-5.5-a/app/Http/Middleware/VerifyCsrfToken.php

    protected $except = [
        'api/*'
    ];


## Copiar codigo da Aplicação

    cd /var/www/MGspa/mg-laravel-5.5-a
    cp ../mg-laravel-5.5/app/Mg app/ -R


## Instalar Tradução

    cd /var/www/MGspa/mg-laravel-5.5-a/resources/lang
    git clone https://github.com/enniosousa/laravel-5.5-pt-BR-localization.git ./pt-BR
    rm -rf pt-BR/.git/


# QUASAR
sudo npm i -g npm
sudo npm install -g vue-cli
sudo npm install -g quasar-cli
quasar init mg-quasar-0.15.10

? Project name (internal usage for dev) mg-quasar-0.15.10
? Project product name (official name) MG Quasar Spa
? Project description Sistema MG Papelaria
? Author Fabio Migliorini <fabio@mgpapelaria.com.br>
? Use ESLint to lint your code? Yes
? Pick an ESLint preset Standard
? Cordova id (disregard if not building mobile apps) org.cordova.quasar.app
? Use Vuex? (recommended for complex apps/websites) Yes
? Use Axios for Ajax calls? Yes
? Use Vue-i18n? (recommended if you support multiple languages) No
? Support IE11? No
? Should we run `npm install` for you after the project has been created? (recom
mended) NPM

   vue-cli · Generated "mg-quasar-0.15.10".
