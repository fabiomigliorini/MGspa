FROM node:20.10.0-alpine
RUN npm i --location=global @quasar/cli@2.3.0
MAINTAINER mgpapelaria.com.br

WORKDIR /opt/www/MGspa/quasar-v2

RUN apk upgrade --update --no-cache
RUN apk add --no-cache bash-completion coreutils autoconf
#RUN npm install -g @quasar/app@1.9.6 --loglevel verbose