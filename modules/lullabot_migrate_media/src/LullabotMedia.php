<?php

namespace Drupal\lullabot_migrate_media;

use Drupal\lullabot_migrate\LullabotMigrationService;
use Drupal\media\Entity\Media;
use Drupal\file\FileInterface;

/**
 * Provides a 'LullabotMedia' service.
 *
 * Helper to create, look up, and manipulate file and media entities during
 * migration.
 */
class LullabotMedia extends LullabotMigrationService {

  /**
   * Search for a media entity linked to a file.
   *
   * @param integer $fid
   *   The id of the file.
   *
   * @return array
   *   The media entity.
   */
  public function find($bundle, $fid) {
    $column = $this->getColumn($bundle);
    $query = $this->entityTypeManager->getStorage('media')->getQuery();
    $query->condition('bundle', $bundle);
    $query->condition($column . '.target_id', $fid);
    $mids = $query->execute();
    if (!empty($mids) && !empty(array_values($mids))) {
      $storage = $this->entityTypeManager->getStorage('media');
      if ($media_entities = $storage->loadMultiple($mids)) {
        return array_shift($media_entities);
      }
    }
    return FALSE;
  }

  /**
   * Creates a media image entity from a file entity.
   *
   * This method will see if a media entity already exists and return it if
   * it does exist. If a media entity doesn't exist, it will be created and
   * returned.
   *
   * @param integer $fid
   *   The id of the file.
   * @param array $attributes
   *   An array of attributes from the original file reference.
   * @param string $version
   *   Options 'D8' or 'D7', whether the provided fid is the D7 or D8 value.
   *
   * @return \Drupal\media\Entity\Media
   *   A media entity.
   */
  public function createMedia($fid, $attributes = [], $version = 'D8') {

    // Log it.
    //$this->logger->notice(
    //  sprintf('Running createMedia() in "%s".', basename(__FILE__, '.php'))
    //);

    // Make sure we're looking for the D8 fid.
    if ($version == 'D7') {
      if (!$new_fid = $this->fid7to8($fid)) {
        $this->logger->warning(
          sprintf('D7 file "%d" does not exist in D8.', $fid)
        );
        return FALSE;
      }
      else {
        $fid = $new_fid;
      }
    }

    // See if the file can be loaded.
    try {
      $file =  $this->entityTypeManager->getStorage('file')->load($fid);
    }
    catch (Exception $e) {
      $this->logger->notice(
        sprintf('Unable to load file "%d": "%s"', $fid, $e->message())
      );
      return FALSE;
    }

    if (!empty($file)) {
      $bundle = $this->getBundle($file);
      // See if there is already a media entity for this file.
      if ($media_entity = $this->find($bundle, $file->id())) {
        // If the media entity already exists, use it.
        //$this->logger->notice(
        //  sprintf('Found an existing media entity "%d" for file "%d".', $media_entity->id(), $file->id())
        //);
        return $media_entity;
      }
      // If a media entity doesn't exist, create one.
      $column = $this->getColumn($bundle);
      $values = [
        'bundle' => $bundle,
        'uid' => $file->getOwnerId(),
        'name' => $file->getFilename(),
        'status' => TRUE,
        'created' => $file->getCreatedTime(),
        'changed' => $file->getChangedTime(),
        $column => [
          'target_id' => $file->id(),
          'alt' => !empty($attributes['alt']) ? $attributes['alt'] : $file->getFilename(),
          'title' => !empty($attributes['title']) ? $attributes['title'] : $file->getFilename(),
          'width' => !empty($attributes['width']) ? $attributes['width'] : NULL,
          'height' => !empty($attributes['height']) ? $attributes['height'] : NULL,
        ],
      ];
      // Add values from D7 file entity fields.
      // The standard D8 file migration doesn't import them since files are not
      // fieldable in D8.
      if ($bundle == 'image') {
        $image_values = $this->oldFieldLookup($file->id(), 'D8');
        foreach ($image_values as $key => $value) {
          switch ($key) {
            // Do nothing with the fid.
            case 'fid':
              break;

            // See if we have a value for alt in the extra field.
            case 'field_file_image_alt_text_value':
              if (!empty($value) && $values[$column]['alt'] == $file->getFilename()) {
                $values[$column]['alt'] = $value;
              }
              break;

            // See if we have a value for title in the extra field.
            case 'field_file_image_title_text_value':
              if (!empty($value) && $values[$column]['title'] == $file->getFilename()) {
                $values[$column]['title'] = $value;
              }
              break;

            default:
              // The old values have keys like 'field_file_image_alt_text_value'.
              // Create a corresponding value for the new media item.
              $key = str_replace('_value', '', $key);
              $values[$key] = $value;
              break;
          }
        }
      }

      // Try to create a new media item.
      try {
        $media_entity = Media::create($values);
        $media_entity->save();
      }
      catch (Exception $e) {
        $this->logger->notice(
          sprintf('Unable to create a media entity for file "%d". "%s"', $file->id(), $e->message())
        );
        return FALSE;
      }

      //$this->logger->notice(
      //  sprintf('Created a new media entity "%d" for file "%d".', $media_entity->id(), $file->id())
      //);
      return $media_entity;
    }

    $this->logger->notice(
      sprintf('Unable to create a media entity for file "%d".', $fid)
    );
    return FALSE;
  }

  /**
   * Get the column names by bundle.
   */
  public function getColumn($bundle) {
    switch ($bundle) {
      case 'audio':
      case 'video':
        $column = 'field_media_' . $bundle . '_file';
        break;

      case 'video_embed':
        $column = 'field_media_video_embed_field';
        break;

      default:
        $column = 'field_media_' . $bundle;
        break;
    }
    return $column;
  }

  /**
   * Deduce the media bundle for a file.
   */
  public function getBundle($file) {
    $bundle = '';
    $mimetype = $file->getMimeType();
    $parts = explode('/', $mimetype);
    $base = $parts[0];

    // This covers all the mimetypes that seem to exist in the source.
    // This logic can be expanded if more are discovered.
    switch ($base) {
      case 'application':
      case 'text':
        $bundle = 'file';
        break;

      case 'image':
      case 'audio':
      case 'video':
        if ($mimetype == 'image/svg+xml') {
          $bundle = 'file';
        }
        else {
          $bundle = $base;
        }
        break;

      default:
        $bundle = 'file';
        break;
    }
    return $bundle;
  }

  /**
   * Delete all migration-related media entities.
   *
   * Delete all media entities if all files have been deleted, or if force is
   * TRUE.
   *
   * Delete only migration-related media entities. A fid > 8000 would be
   * a manually-created file that should not be deleted. All new fids were
   * forced to start with 8000.
   *
   * @see lullabot_migrate.install
   */
  public function deleteAll($force = FALSE) {

    // See if there are any files.
    $query = $this->entityTypeManager->getStorage('file')->getQuery();
    $query->condition('fid', 8000, '>=');
    $fids = $query->execute();
    $mids = [];

    // If all files are gone from a rollback, or we are forcing a fresh start,
    // delete all media entities.
    if (empty($fids) || $force) {

      // Get an array of all media entities.
      $query = $this->entityTypeManager->getStorage('media')->getQuery('OR');
      $query->condition('field_media_image.entity.fid', 8000, '<=');
      $query->condition('field_media_file.entity.fid', 8000, '<=');
      $query->condition('field_media_audio_file.entity.fid', 8000, '<=');
      $query->condition('field_media_video_file.entity.fid', 8000, '<=');

      // Video embeds are not files, just delete all of them.
      $query->condition('field_media_video_embed_field.value', '', '<>');
      $mids = $query->execute();

      if (!empty($mids)) {
        // Delete the media entities.
        $controller = $this->entityTypeManager->getStorage('media');
        $entities = $controller->loadMultiple($mids);
        $controller->delete($entities);

        // The file usage table doesn't get cleaned up when entities are deleted.
        // Forcibly remove them to avoid bad totals as we run and rollback
        // migrations.
        // @see https://www.drupal.org/project/media_entity/issues/2858613
        $this->connection->delete('file_usage')
          ->condition('id', $mids, 'IN')
          ->condition('type', 'media')
          ->execute();

      }
    }
    return $mids;

  }

  /**
   * Find the D7 fid for a D8 fid.
   */
  function fid8to7($fid) {
     $query = $this->connection->select('migrate_map_d7_file', 'm')
       ->fields('m', ['sourceid1'])
       ->condition('m.destid1', $fid);
     $value = $query->execute()->fetchField();
     return $value;
   }

  /**
   * Find the D8 fid for a D7 fid.
   */
  function fid7to8($fid) {
     $query = $this->connection->select('migrate_map_d7_file', 'm')
       ->fields('m', ['destid1'])
       ->condition('m.sourceid1', $fid);
     $value = $query->execute()->fetchField();
     return $value;
   }

  /**
   * Look up additional file_entity field values in the D7 database.
   *
   * This could be done with additional migration(s), but that would only work
   * if the media entities were also being created by the migration. There
   * will eventually be a migration path for this, but we're just doing it
   * manually to avoid waiting until a core media migration path is available.
   */
  function oldFieldLookup($fid, $version = 'D8') {

    // Log it.
    //$this->logger->notice(
    //  sprintf('Running oldFieldLookup for fid "%d" in "%s".', $fid, basename(__FILE__, '.php'))
    //);

    // Get the D7 fid, which could be different than the D8 fid.
    if ($version == 'D7') {
      $fid = $this->fid8to7($fid);
    }

    // Look up the field data for this fid in the D7 source.
    $fields = [
      'field_file_image_alt_text',
      'field_file_image_caption_text',
      'field_file_image_title_text',
    ];

    // Switch to Drupal 7 database.
    $query = $this->connection7->select('file_managed', 'f');
    $query->condition('f.fid', $fid);
    $query->fields('f', ['fid']);
    $query->fields('n0', [$fields[0] . '_value']);
    $query->fields('n1', [$fields[1] . '_value']);
    $query->fields('n2', [$fields[2] . '_value']);
    $query->leftJoin('field_data_' . $fields[0], 'n0', 'f.fid = n0.entity_id');
    $query->leftJoin('field_data_' . $fields[1], 'n1', 'n0.entity_id = n1.entity_id');
    $query->leftJoin('field_data_' . $fields[2], 'n2', 'n1.entity_id = n2.entity_id');
    $values = $query->execute()->fetchAssoc();

    return $values;
  }

  /**
   * Find all the unused files after migration is complete.
   *
   * Implemented in PostMigrationSubscriber.
   *
   * @return array
   *   Returns an array of all files that have no entry in usage table.
   */
  function findOrphans() {

    // Select f.fid, count from file_managed as f left join file_usage as u on f.fid=u.fid where count IS NULL;
    $query = $this->connection->select('file_managed', 'f');
    $query->leftJoin('file_usage', 'u', 'f.fid = u.fid');
    $query->fields('f', ['fid']);
    $query->isNull('count');
    $values = $query->execute()->fetchCol();

    return $values;
  }

  /**
   * Search for an embedded media entity linked to a uri.
   *
   * @param string $uri
   *   The uri of the entity.
   *
   * @return array
   *   The media entity.
   */
  public function findUri($bundle, $uri) {
    $column = $this->getColumn($bundle);
    $query = $this->entityTypeManager->getStorage('media')->getQuery();
    $query->condition('bundle', $bundle);
    $query->condition($column . '.value', $uri);
    $mids = $query->execute();
    if (!empty($mids) && !empty(array_values($mids))) {
      $storage = $this->entityTypeManager->getStorage('media');
      if ($media_entities = $storage->loadMultiple($mids)) {
        return array_shift($media_entities);
      }
    }
    return FALSE;
  }

  /**
   * Creates a media video embed entity from embedded item uri.
   *
   * This method will see if a media entity already exists and return it if
   * it does exist. If a media entity doesn't exist, it will be created and
   * returned.
   *
   * @param string $uri
   *   The uri of the media item.
   * @param array $attributes
   *   An array of attributes to add.
   *
   * @return \Drupal\media\Entity\Media
   *   A media entity.
   */
  public function createMediaUri($uri, $attributes) {

    // Log it.
    //$this->logger->notice(
    //  sprintf('Running createMediaUri() in "%s".', basename(__FILE__, '.php'))
    //);

    $bundle = 'video_embed';

    // See if there is already a media entity for this item.
    if ($media_entity = $this->findUri($bundle, $uri)) {
      // If the media entity already exists, use it.
      //$this->logger->notice(
      //  sprintf('Found an existing media entity "%d" for path "%s".', $media_entity->id(), $uri)
      //);
      return $media_entity;
    }
    // If a media entity doesn't exist, create one.
    $values = [
      'bundle' => $bundle,
      'uid' => !empty($attributes['uid']) ? $attributes['uid'] : 1,
      'name' => !empty($attributes['title']) ? $attributes['title'] : 'Created from migration',
      'status' => TRUE,
      'created' => !empty($attributes['created']) ? $attributes['created'] : REQUEST_TIME,
      'changed' => !empty($attributes['changed']) ? $attributes['changed'] : REQUEST_TIME,
      'field_media_video_embed_field'=> [
        'value' => $uri,
      ],
    ];

    // Try to create a new media item.
    try {
      $media_entity = Media::create($values);
      $media_entity->save();
    }
    catch (Exception $e) {
      $this->logger->notice(
        sprintf('Unable to create a media entity for uri "%s". "%s"', $uri, $e->message())
      );
      return FALSE;
    }

    //$this->logger->notice(
    //  sprintf('Created a new media entity "%d" for uri "%s".', $media_entity->id(), $uri)
    //);
    return $media_entity;
  }

  /**
   * Create a media embed item to insert into text.
   *
   * @return string
   *   Returns the media embed item for this media.
   */
  public function createMediaEmbedText($media_entity, $values) {
    $style = !empty($values['style']) ? $values['style'] : 'wide';
    $data_align = !empty($values['data_align']) ? $values['data_align'] : NULL;
    $data_caption = !empty($values['data_caption']) ? $values['data_caption'] : NULL;
    $attributes = [
      'data-embed-button' => 'media_entity_embed',
      'data-entity-embed-display' => 'view_mode:media.' . $style,
      'data-entity-type' => 'media',
      'data-entity-uuid' => $media_entity->get('uuid')->value,
    ];
    if (!empty($data_align)) {
      $attributes['data-align'] = $data_align;
    }
    $attributes['data-caption'] = $data_caption;

    $attribute_values = [];
    // Not using Attribute class here because it will double-escape some
    // values. The source data should be safe and not need escaping.
    foreach ($attributes as $key => $value) {
      $attribute_values[] = $key . '="' . $value . '"';
    }
    $embed = '<drupal-entity ' . implode(' ', $attribute_values) . '></drupal-entity>';
    return $embed;

  }

  /**
   * Scan the D7 body text for embedded values.
   *
   * Returns a list of node ids that contain the $string value.
   */
  function embedLookup($string) {

    // Log it.
    $this->logger->notice(
      sprintf('Running embedLookup for string "%s" in "%s".', $string, basename(__FILE__, '.php'))
    );

    // Switch to Drupal 7 database.
    $query = $this->connection7->select('field_data_body', 'b');
    $query->fields('n', ['entity_id']);
    $query->condition('body_value', '%' . db_like($string) . '%', 'LIKE');
    $values = $query->execute()->fetchAssoc();

    return $values;
  }
}
