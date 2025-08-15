FROM ubuntu:22.04

RUN apt-get update && apt-get install -y \
    php-cli \
    iputils-ping \
    iproute2 \
    net-tools \
    dnsutils \
    netcat-openbsd \
    curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . .

RUN chmod +x troubleshoot.php

CMD ["php", "index.php"]