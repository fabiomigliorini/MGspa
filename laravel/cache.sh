#!/bin/bash
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan view:cache
sudo php artisan event:cache
sudo php artisan cache:clear
