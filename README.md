#Lullabot custom migrations

## Media
The migrate media module transforms embedded images in D7 nodes to switch them from json images:

```
[image:{
"fid":"4655",
"width":"medium",
"border":true,
"position":"","treatment":""
}]
```

to media embed tags:

```
<drupal-entity 
data-embed-button="media_entity_embed" 
data-entity-embed-display="view_mode:media.narrow" 
data-entity-type="media" 
data-entity-id="4655">
</drupal-entity>

```
There is also code to transform embedded Vimeo and YouTube videos to media embeds.

The transformation is done in a custom process plugin, LullabotEmbeddedContent, using several custom services:

```
src/LullabotInlineImages.php
src/LullabotInlineVideos.php
src/LullabotMedia.php
```
The PostMigrationSubscriber handles the deletion of media entities when the file migration is rolled back, since they otherwise don't get removed on rollback.

## D8 formats
In `hook_migration_plugins_alter()`, the migrated node bodies are adjusted to map D7 format to D8 formats, changing all entities and entity revisions in a single place.

## Pathauto and menus
Paths are constructed by the Migrate Paths module as each node is migrated in, by concatonating parent and child slugs.

Menus are created in PostMigrationSubscriber, after the nodes that are contained in the menus have been created.


## Configuration vs content migration
The migration was executed in steps. First configuration was migrated in and adjusted. The new configuration has been stored in the config sync repository. The code in `hook_migrations_plugins_alter` ensures that future migrations omit the configuration migrations (by unsetting them) so the configuration changes don't get overwritten and only content migrations will be executed.
