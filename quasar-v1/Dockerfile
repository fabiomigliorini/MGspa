FROM debian:stretch

MAINTAINER mgpapelaria.com.br

RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get install --reinstall systemd -y \
    && apt-get install -y \
    lsb-release \
    apt-transport-https \
    wget \
    vim \
    rsync \
    curl


RUN curl -sL https://deb.nodesource.com/setup_8.x | bash -
RUN apt-get install -y nodejs
RUN npm install -g @quasar/cli


CMD /bin/bash
