# DoomtownDB

## Installation

This guide assumes you know how to use the command-line,
and that your machine has PHP (7.4), MySQL (5.7+), and a web-server (e.g. Apache 2) installed.

- Install [composer](https://getcomposer.org/download/).
- Clone this repo.
- `cd` to it.
- Run `composer install` to install package dependencies
- Copy the `.env` file to `.env.local` and modify its configuration settings to your needs.
- Run `php bin/console doctrine:database:create` to create the  database, it will be empty.
- Run `php bin/console doctrine:migrations:migrate` to set up the database structure (e.g. tables).
- Load the default data set. See [install/README.md](install/README.md) for instructions.
- Run `php bin/console bazinga:js-translation:dump public/js` to export translation files for the frontend.
- Run `php bin/console fos:js-routing:dump --target=public/js/fos_js_routes.js` to export routes for the frontend.
- Configure your web server to point to the `/public` directory as your web root.

## Set up an admin account

- Run `php bin/console fos:user:create <username> <email> <password>` to create a new user account.
- Run `php bin/console fos:user:activate <username>` to activate the new user account.
- Run `php bin/console fos:user:promote --super <username>` to grant the user super-administrative powers.
