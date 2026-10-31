#!/bin/sh
# Crea la base de datos de pruebas la primera vez que se inicializa el volumen de MySQL.
set -e

mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE_TEST\`;
    GRANT ALL PRIVILEGES ON \`$DB_DATABASE_TEST\`.* TO '$MYSQL_USER'@'%';
EOSQL
