# D7 to D8 Custom Migration

Custom migration code for miscellaneous D8 fields and content. This module will force a number
of custom fields that don't currently have migration handling to migrate, including node reference,
address, geofield, and geolocation. It also adds handling for converting numeric formats to text
formats, limiting the blocks that are migrated in, and otherwise step in and adjust a migration.

Everything is done using hook_migrate_prepare_row(), which includes the ability to both alter the
source data and alter the migration process steps. Each of the include files needs to be adjusted
for the specific site, identifying the particular field types and field names that should be touched.
These can also be used as examples for other fields you might need to migrate.

The hook_migrate_prepare_row() is provided by the Migrate Plus module, so that is required for this to work.
You also need to be able to run drush migrate-upgrade. That will eventually be available in Drush 8
core, and may already be there, depending on what version of Drush you are running. If it's not, you
can use the Migrate Upgrade module to provide it.

A common series of steps would be:

- Create a new D8 database.
- Enable all the necessary modules, including:
 - Migrate
 - Migrate Drupal
 - Migrate Tools
 - Migrate Plus
 - Devel
 - Kint
 - All field, content, widget, and formatter modules needed for the final site

Then:

```
drush migrate-upgrade --legacy-db-url="mysql://root:password@localhost/dbname" --legacy-root="http://d7site.com" --configure-only
```

To see all the migrations that will run:
```
drush ms
```

To do the import:
```
drush mi --all
```

Watch the results. If particular migrations are throwing errors you can debug them by adding code like this to the hook implementation:
```
  if ($migration->id() == 'd7_field_formatter_settings') {

    drush_print_r($row);
    drush_print_r($migration);
  }

```
As you're debugging, the migration you're trying to fix will get stuck and never stop running. To fix that, do the following, using the name of the broken migration:
```
drush php-eval 'var_dump(Drupal::keyValue("migrate_status")->set('d7_field_formatter_settings', 0))'
```

It is possible to roll back and re-run the migration, but field configuration won't ever really roll back, only content. If you need to re-do a field
migration you'll need to drop the database and start over. It's best to create a database dump right before you start working on the migration that you
can reload when this happens.

I used one core patch as well, and it is required for the code to import references:

A patch to migrate D7 entityreference fields, which includes some code needed to migrate node reference field settings:
- https://www.drupal.org/files/issues/2611066-9.patch
