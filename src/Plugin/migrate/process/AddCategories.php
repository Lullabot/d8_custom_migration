<?php

namespace Drupal\lullabot_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\ProcessPluginBase;

/**
 * Add categories to migrated content.
 *
 * @MigrateProcessPlugin(
 *   id = "add_categories"
 * )
 *
 * @code
 *  field_categories:
 *    plugin: add_categories
 * @endcode
 */
class AddCategories extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $title = $row->getSourceProperty('title');
    $author = $row->getSourceProperty('node_uid');
    $values = [];

    if (stristr($title, 'weather')) {
      $values[] = 2; // finance
    }
    if (stristr($title, 'retreat')) {
      $values[] = 3; // retreats
    }
    if (stristr($title, 'tugboat')) {
      $values[] = 12; // tugboat
    }
    if (stristr($title, 'futurecast')) {
      $values[] = 7; // sales
    }
    if (stristr($title, 'director')) {
      $values[] = 14; // vision
    }
    if ($author == 56) { // bskowron
      $values[] = 7; // sales
    }
    if ($author == 28) { // jponch
      $values[] = 10; // design & strategy
    }
    if ($author == 138) { // ellie
      $values[] = 6; // marketing
    }
    if ($author == 92) { // kris
      $values[] = 1; // human resources
    }
    if ($author == 26) { // matt
      $values[] = 14; // vision
    }
    $values = array_unique($values);
    return $values;
  }

}
