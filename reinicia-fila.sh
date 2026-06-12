#!/bin/bash

docker exec mglara-mglara-1 php artisan queue:restart
#docker exec laravel-api-mgspa-1 php artisan queue:restart
# O sinal de restart vai pro Redis e todas as 16 réplicas (api-worker-N)
# escutam — basta rodar uma vez no container da api.
docker exec mgspa-api php artisan queue:restart

