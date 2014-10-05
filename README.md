# Quick notes for installation

- Go into the directory where your server will reside
- Fork the repo and clone it: `git clone https://github.com/yourname/netrunnerdb`
- This creates a directory named netrunnerdb. This has to be your apache DOCROOT. 
- Go into it.
- Install Composer: `curl -s http://getcomposer.org/installer | php`
- Install the vendor libs: `php composer.phar install`
- Create the database: `php app/console doctrine:database:create`
- Create the tables: `php app/console doctrine:schema:update --force`
- If the above command fails, edit app/config/parameters.yml and try again
- change card.ts to be 'on update CURRENT_TIMESTAMP'
- Import the data: mysql -u root -p netrunnerdb < netrunnerdb-cards.sql
- Configure your web server with the correct DocRoot
- Point your browser to `/web/app_dev.php`

# Quick notes for update

When you update your repository, run the following commands:

- `php composer.phar self-update`
- `php composer.phar update`
- `php app/console doctrine:schema:update --force`
- `php app/console cache:clear --env=dev`

## Deck of the Week

To update the deck of the week on the front page:

- `php app/console highlight` 

## Setup an admin account

- register
- if you haven't setup mail delivery, manually activate your account
- run `php app/console fos:user:promote --super <username>`

## Add cards

- login with admin-level account
- go to `/admin/card`, `/admin/pack`, `/admin/cycle`, etc.

## Add cards with Excel on existing pack

- note the code of the pack (wla for What Lies Ahead, etc.). let's say it's xxx
- login with admin-level account
- go to /api/set/xxx.xls
- open the downloaded file and add your cards
- go to /admin/excel/form and upload your file, click 'Validate' on confirmation screen
- actually the excel file can be the one from another pack, just replace the 2nd column

# Misc Notes

- your php module must be configured with `mbstring.internal_encoding = UTF-8`
