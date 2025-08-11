#!/bin/bash

sudo service supervisor stop
sudo service supervisor stop
sleep 2
sudo killall php
sleep 2
sudo killall php
sleep 2
sudo service supervisor start



