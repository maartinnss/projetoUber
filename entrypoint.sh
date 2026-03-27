#!/bin/bash
set -e

# Railway passa a porta dinâmica pela variável PORT. Apache ouve na 80 nativamente.
LISTEN_PORT=${PORT:-80}
DOC_ROOT=${APACHE_DOCUMENT_ROOT:-/var/www/html/public}

echo "Configurando Apache para porta $LISTEN_PORT e DocumentRoot $DOC_ROOT"

# 1. Ajusta a porta apenas no ports.conf (regex segura de palavra inteira)
sed -i "s/\b80\b/$LISTEN_PORT/g" /etc/apache2/ports.conf

# 2. Ajusta o VirtualHost padrão (000-default.conf)
# Troca a porta no VirtualHost
sed -i "s/:80>/:$LISTEN_PORT>/g" /etc/apache2/sites-available/000-default.conf
# Troca o DocumentRoot padrão (/var/www/html) para o novo (/var/www/html/public)
sed -i "s!/var/www/html!$DOC_ROOT!g" /etc/apache2/sites-available/000-default.conf

# 3. Garante que nenhum MPM conflitante esteja carregado (correção do erro AH00534)
# O PHP-Apache usa o mpm_prefork por padrão.
# Desativamos o mpm_event se ele estiver ativo.
if [ -f /etc/apache2/mods-enabled/mpm_event.load ]; then
    a2dismod mpm_event || true
fi
a2enmod mpm_prefork || true

# Inicia o Servidor
exec docker-php-entrypoint apache2-foreground
