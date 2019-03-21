#Lullabot Migrate Media

## Inline images
This code transforms embedded images in D7 nodes to switch them from json images:

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
The transformation is done in `hook_entity_presave` using a custom service.

```
src/LullabotInlineImages.php
```

## File/Image to Media entities
File and image fields are migrated in normally, then transformed to media entities in `hook_migrate_prepare_row()`, using a custom service.

```
src/LullabotMedia.php
```


