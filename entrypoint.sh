#!/bin/bash
set -e

# Railway passa a porta dinâmica pela variável PORT. Apache ouve na 80 nativamente.
# Se PORT existe, substituímos no apache. Se não, assume 80.
LISTEN_PORT=${PORT:-80}

echo "Configurando Apache para ouvir na porta: $LISTEN_PORT"

sed -i "s/80/$LISTEN_PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Inicia o Servidor
exec docker-php-entrypoint apache2-foreground
