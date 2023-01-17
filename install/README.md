# What is this?

This directory contains a SQL script, `data.sql`, which provides the default data set needed to populate a fresh instance of DoomtownDB.

**Do not attempt to run this script against a populated database.**

## Installation

Run this script against your database _after_ creating the schema and running all migrations.

```
mysql -u DB_USER -p DB_NAME < data.sql
```

## Regenerating this data file

Since production data will change over time, i.e. new cards are added  whenever a new expansion for the game is released,
it is necessary to update the default data set every so often.

In order to do so, the contents of the following tables need to be dumped out to file again.

- card
- cycle
- gang
- pack
- shooter
- suit
- tournament
- type

Run the command below against the production database.

```
mysqldump -u DB_USER -p --no-create-info --skip-extended-insert DB_NAME card cycle gang pack shooter suit tournament type > data.sql
```

Then commit the updated `data.sql` script to version control.
