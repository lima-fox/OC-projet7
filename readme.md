# OC Projet 7 API

## Prerequisites
- PHP 7.4

## Installation

### Dependencies
Run composer
``` 
composer install
```

### Configuration
create your `.env`
```
cp .env.example .env
```

Setup your database credentials
```
DATABASE_URL="postgresql://db_user:db_password@127.0.0.1:5432/db_name?serverVersion=13&charset=utf8"
```

Run the db migrations
```
php bin/console doctrine:migrations:migrate
```

### Setup your OAuth Server
Generate ssl keys on project directory

- To generate the private key run this command 
```
openssl genrsa -out private.key 2048
```
- Then extract the public key from the private key:
```
openssl rsa -in private.key -pubout -out public.key
```
Configure the OAUTH2_ENCRYPTION_KEY in `.env`
- Run this command
```
php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'
```
Create a customer
```
trikoder:oauth2:create-client [<identifier> [<secret>]]
```


## OpenAPI Documentation
browse `/doc`
