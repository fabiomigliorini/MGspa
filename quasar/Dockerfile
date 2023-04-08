FROM node:10-alpine

MAINTAINER mgpapelaria.com.br

WORKDIR /opt/www/MGspa/quasar

RUN apk upgrade --update --no-cache
RUN apk add --no-cache bash-completion coreutils autoconf
#RUN npm install -g @quasar/app@1.9.6 --loglevel verbose
RUN npm install -g @quasar/cli@1.3.2 --loglevel verbose
