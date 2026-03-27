#!/bin/bash
set -e

# Railway passa a porta dinâmica pela variável PORT. Apache ouve na 80 nativamente.
LISTEN_PORT=${PORT:-80}

echo "Configurando Apache para ouvir na porta: $LISTEN_PORT"

# Somente substitui se ainda estiver com a porta 80 para evitar loops infinitos de '80808080'
# Substitui o Listen no ports.conf
sed -i "s/^Listen 80/Listen $LISTEN_PORT/g" /etc/apache2/ports.conf
# Substitui o VirtualHost no sites-available
sed -i "s/:80>/:$LISTEN_PORT>/g" /etc/apache2/sites-available/000-default.conf

# Inicia o Servidor
exec docker-php-entrypoint apache2-foreground
