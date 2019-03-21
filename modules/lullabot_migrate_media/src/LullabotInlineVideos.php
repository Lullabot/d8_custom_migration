<?php

namespace Drupal\lullabot_migrate_media;

/**
 * Provides a 'LullabotInlineImages' service.
 *
 * Converts embedded videos to the D8 entity embed format.
 */
class LullabotInlineVideos {

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

    $pattern = '@\[Vimeo:(.*?)\]@i';
    $callback = [__CLASS__, 'vimeoValues'];
    $value = preg_replace_callback($pattern, $callback, $value);

    $pattern = '@\[YouTube:(.*?)\]@i';
    $callback = [__CLASS__, 'youTubeValues'];
    $value = preg_replace_callback($pattern, $callback, $value);

    return $value;
  }

  /**
   * Callback for embedded Vimeo values.
   *
   * Replacement logic.
   *
   * @param array $matches
   *   An array of one set of matches from preg_replace_callback().
   *
   * @return string
   *   A string that replaces the original text, $matches[0].
   */
  public function vimeoValues($matches) {
    $values = [];
    $original = $matches[0];
    $vid = $matches[1];
    $uri = 'https://vimeo.com/' . $vid;

    $media_helper = \Drupal::service('lullabot_migrate_media.media');
    $style = 'wide';
    $attributes = $this->metadata;
    if ($media_entity = $media_helper->createMediaUri($uri, $attributes)) {
      $attributes += [
        'style' => $style,
      ];
      return $media_helper->createMediaEmbedText($media_entity, $attributes);
    }
    else {
      return $original;
    }
  }

  /**
   * Callback for embedded YouTube values.
   *
   * Replacement logic.
   *
   * @param array $matches
   *   An array of one set of matches from preg_replace_callback().
   *
   * @return string
   *   A string that replaces the original text, $matches[0].
   */
  public function youTubeValues($matches) {
    $values = [];
    $original = $matches[0];
    $vid = $matches[1];
    $uri = 'https://youtube.com/watch?v=' . $vid;

    $media_helper = \Drupal::service('lullabot_migrate_media.media');
    $style = 'wide';
    $attributes = $this->metadata;
    if ($media_entity = $media_helper->createMediaUri($uri, $attributes)) {
      $attributes += [
        'style' => $style,
      ];
      return $media_helper->createMediaEmbedText($media_entity, $attributes);
    }
    else {
      return $original;
    }
  }

}
