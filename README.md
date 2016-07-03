# D7 to D8 Custom Migration

**Note: This module is not something you can blindly install and use, it requires some adjustment
to the specific fields and content types on a particular site. So it should be used as a starting
point for your own custom code! This code has been used successfully to migrate real-world sites, but your mileage may vary!**

This module contains custom migration code for miscellaneous D8 fields and content. It will migrate a number
of custom fields that wouldn't otherwise work, including node reference, user reference,
address, geofield, and geolocation. It also adds handling for converting numeric formats to text
formats, limiting the blocks that are migrated in, and otherwise providing ways to step in and adjust a migration.

This is designed to be re-run over and over while debugging and adjusting a Drupal to Drupal migration. For best results,
create a bash script to make it easy to drop the database and start the whole process from scratch as many times as necessary.
I run it over and over, debugging an error at a time and trying to figure out how to pull in more missing data.

Everything is done using ```hook_migrate_prepare_row()```, a powerful hook which includes the ability to both alter the
source data and alter the migration process steps. Each of the include files needs to be adjusted
for the specific site, identifying the particular field types and field names that should be touched.
These can also be used as examples for other fields you might need to migrate.

The hook makes it easy to try changes out to see what does and doesn't provide the right results.
You could create custom YAML files for the migration, and update them using the changes identified in this hook, then remove them from the hook implementation.
It is also possible to change the code in the module to match your needs, then enable this module while the migration is running to avoid the need for creating custom YAML files.
Making changes to the processing on every individual row is not as effecient as adjusting the process configuration directly by tweaking
the YAML files, but doing this on-the-fly does work.

The ```hook_migrate_prepare_row()``` is provided by the [Migrate Plus module](https://www.drupal.org/project/migrate_plus), so that is required for this to work.
You also need to be able to run drush migrate-upgrade. That will eventually be available in Drush 8
core, and may already be there, depending on what version of Drush you are running. If it's not, you
can use the [Migrate Upgrade module](https://www.drupal.org/project/migrate_upgrade) to provide it.

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
  if ($migration->id() == 'update_d7_field_formatter_settings') {

    drush_print_r($row);
    $process = $migration->getProcess();
    drush_print_r($process);
  }

```

Then re-run just that migration with ```drush mi update_d7_field_formatter_settings``` and watch the output. This code will make it possible to see which migration row was being processed when the migration aborted. That can be used to deduce
what needs to be adjusted in the migration, or that this is a row that should just be skipped. You can also see what the current process looks like. It may be that it's the process that needs to be adjusted, like adding a new value to the map
of before and after values for fields, widgets, and formatters.

A common problem in upgrades are custom view modes that existed in the D7 site which don't exist in the D8 site.
The config/optional directory contains an example of a custom view mode that needs to be added to the D8 site.
Other common problems are non-core fields, formatters, and widgets that are missing in the D8 site. Missing fields
will just be ignored, but if migrated fields use non-core formatters or widgets the migration will explode with a
"Missing dependencies" error, so it's important to make sure something has been done to either install them in D8 or
add code to massage them into valid values. This is part of what is needed to fix the migration of node references,
for instance.

As you're debugging, the migration you're trying to fix may get stuck and never stop running. To fix that, do the following, using the name of the broken migration:

```
drush php-eval 'var_dump(Drupal::keyValue("migrate_status")->set('update_d7_field_formatter_settings', 0))'
```

It is possible to roll back and re-run the migration, but field configuration won't ever really roll back, only content. If you need to re-do a field
migration you'll need to drop the database and start over. It's best to create a database dump right before you start working on the migration that you
can reload when this happens.

I used one core patch as well, and it is required for the code to import node and user references. It is a patch to migrate D7 entityreference fields, which includes some code also needed to migrate node reference field settings:

- [https://www.drupal.org/files/issues/field-upgrade_path_to_entity_reference_field_from_7.x-2611066-22-D8.1.x.patch](https://www.drupal.org/files/issues/field-upgrade_path_to_entity_reference_field_from_7.x-2611066-22-D8.1.x.patch)
