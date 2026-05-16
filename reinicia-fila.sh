#!/bin/bash

docker exec mglara-mglara-1 php artisan queue:restart
docker exec laravel-api-mgspa-1 php artisan queue:restart
