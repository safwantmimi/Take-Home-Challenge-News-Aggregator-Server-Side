#!/bin/bash
#This is the db that we're gooing to use to fetch our data later ..

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-EOSQL
    CREATE DATABASE news;
    CREATE USER sfn WITH PASSWORD 'safouan_tmimi';
    GRANT ALL PRIVILEGES ON DATABASE news TO sfn;
EOSQL
