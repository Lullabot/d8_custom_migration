<?php

namespace Drupal\lullabot_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Provides a 'LullabotEmbeddedContent' migrate process plugin.
 *
 * Calls services to transform embedded content in the body field during
 * migration.
 *
 * - Converts D7 json image token to the D8 entity embed format.
 * - Converts Vimeo and YouTube embeds to D8 entity embeds.
 *
 * @MigrateProcessPlugin(
 *  id = "lullabot_embedded_content"
 * )
 */
class LullabotEmbeddedContent extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $body = !empty($value['value']) ? $value['value'] : '';
    $summary = !empty($value['summary']) ? $value['summary'] : '';
    $format = !empty($value['format']) ? $value['format'] : 'filtered_html';
    switch($format) {
      case 'filtered_html':
        $new_format = 'basic_html';
        break;

      case 'markdown':
        $new_format = 'markdown';
        break;

      case 'plain_text':
        $new_format = 'plain_text';
        break;

    }

    // Pass the body through all the services that apply.
    $metadata = [
      'nid' => $row->getSourceProperty('nid'),
      'title' => $row->getSourceProperty('title'),
      'created' => $row->getSourceProperty('created'),
      'changed' => $row->getSourceProperty('changed'),
      'uid' => $row->getSourceProperty('node_uid'),
    ];
    $body = \Drupal::service('lullabot_migrate_media.inline_images')->findAndReplace($body, $metadata);
    $body = \Drupal::service('lullabot_migrate_media.inline_videos')->findAndReplace($body, $metadata);
    $item = [
      'value' => $body,
      'summary' => $summary,
      'format' => $new_format,
    ];
    return $item;
  }

}
