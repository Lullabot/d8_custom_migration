<?php

namespace Drupal\lullabot_migrate_media\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Provides a 'LullabotCreateMedia' migrate process plugin.
 *
 * Creates new media items from legacy file/image fields and links to them.
 *
 * @MigrateProcessPlugin(
 *  id = "lullabot_create_media"
 * )
 */
class LullabotCreateMedia extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Node source fields create in an array value, multifields just an id.
    if (!is_array($value) && !empty($value)) {
      $value = ['fid' => $value];
    }
    elseif (empty($value)) {
      return $value;
    }
    $media_helper = \Drupal::service('lullabot_migrate_media.media');
    $item = [];
    if (array_key_exists('fid', $value) && !empty($value['fid'])) {
      $attributes = [
        'alt' => !empty($value['alt']) ? $value['alt'] : '',
        'title' => !empty($value['title']) ? $value['title'] : '',
        'width' => !empty($value['width']) ? $value['width'] : '',
        'height' => !empty($value['height']) ? $value['height'] : '',
      ];
      // Find or create a media entity for this file, then link to it.
      if ($media_entity = $media_helper->createMedia($value['fid'], $attributes, 'D7')) {
        $item['target_id'] = $media_entity->id();
      }
    }
    // Return the new value, formatted correctly for the media field that is
    // being created.
    return $item;

  }

}
