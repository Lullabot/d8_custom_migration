<?php

namespace Drupal\lullabot_migrate_media;

/**
 * Provides a 'LullabotInlineImages' service.
 *
 * Converts D7 json image token to the D8 entity embed format.
 */
class LullabotInlineImages {

  /**
   * Find and replace string.
   *
   * @param string $value
   *   A string value that might contain the json image embed used in D7.
   *
   * @return string
   *   The string with replaced values.
   */
  public function findAndReplace($value, $metadata) {
    $this->metadata = $metadata;
    $pattern = '@\[image:\{"fid":"(\d+)","width":"(\w+)","border":(true|false)(,"position":"(\w+)?")?(,"treatment":"(\w+)?")?\}\]@i';
    $callback = [__CLASS__, 'replaceValues'];
    $result = preg_replace_callback($pattern, $callback, $value);
    return $result;
  }

  /**
   * Callback for findAndReplace() media entity.
   *
   * Replacement logic.
   *
   * @param array $matches
   *   An array of one set of matches from preg_replace_callback().
   *
   * @return string
   *   A string that replaces the original text, $matches[0].
   */
  public function replaceValues($matches) {

    $values = [];
    $original = $matches[0];
    $fid = $matches[1];
    $width = $matches[2]; // Options: 'full', 'half', 'medium', 'original'
    $border = $matches[3]; // Options: 'true', 'false'
    $position = !empty($matches[5]) ? $matches[5] : '';  // Options: 'right', 'left',
    $treatment = !empty($matches[7]) ? $matches[7] : ''; // Options: 'none', 'contained'

    if (!empty($width) && ($width == 'full' || $width == 'original' || $width == 'medium')) {
      $style = 'wide';
    }
    else { // 'half'
      $style = 'narrow';
    }

    $media_helper = \Drupal::service('lullabot_migrate_media.media');

    $file_attributes = $this->metadata;
    // Find or create the matching media entity.
    if ($media_entity = $media_helper->createMedia($fid, $file_attributes, 'D7')) {
      $values = [
        'style' => $style,
        'data_caption' => $media_entity->field_file_image_caption_text->value,
      ];
      if (!empty($position) && ($position == 'right' || $position == 'left')) {
        $values['data_align'] = $position;
      }
    }

    // $attributes will be empty if no matching media entity was found.
    if (!empty($media_entity) && !empty($values)) {
      return $media_helper->createMediaEmbedText($media_entity, $values);
    }
    else {
      return $original;
    }
  }

}
