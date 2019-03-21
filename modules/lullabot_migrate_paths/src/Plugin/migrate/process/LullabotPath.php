<?php

namespace Drupal\lullabot_migrate_paths\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;


/**
 * Create path for imported nodes.
 *
 * @MigrateProcessPlugin(
 *   id = "lullabot_path"
 * )
 *
 * The path does not exist in D7, so we have to create it in the migration,
 * using the same logic that is used to create an array of parent slugs in D7.
 *
 * @param string $value
 *   An array of the slug of the current node and the parent id, if any.
 *
 * @return string
 *   Final path, node and parent slug(s), including parents of parents.
 *
 * Use this plugin as follows:
 *
 * @code
 * parentid:
 *   plugin: migration_lookup
 *   migration: d7_node_section
 *   source: 'field_parent/0/target_id'
 * # Turn off automatic pathauto processing.
 * path/pathauto:
 *   plugin: default_value
 *   default_value: 0
 * path/langcode:
 *   plugin: default_value
 *   default_value: und
 * path/alias:
 *   plugin: lullabot_path
 *   source:
 *     - 'field_slug/0/value'
 *     - '@parentid'
 * @endcode
 *
 */
class LullabotPath extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Log it.
    //\Drupal::logger('lullabot_migrate_path')->notice(
    //  sprintf('Running transform() in "%s".', basename(__FILE__, '.php'))
    //);

    if (!is_array($value) || count($value) != 2) {
      throw new MigrateException('The source should include the slug and parent id.');
    }
    $slug = $value[0];
    $parent = $value[1];

    // Log the problem but do not throw an error.
    if (empty($slug)) {
      //throw new MigrateException('Missing slug value.');
      \Drupal::logger('lullabot_migrate_path')->notice(
        sprintf('Missing slug value replaced with home page.')
      );
      $slug = 'node';
    }

    $path = '/';
    if (!empty($parent)) {
      $parent_slugs = $this->parentSlug($parent);
      if (!empty($parent_slugs)) {
        $path .= implode('/', $parent_slugs) . '/';
      }
    }
    $path .= $slug;

    return $path;
  }

  /**
   * Parent slug path.
   *
   * Generates the tree of slugs that are above the given node, iterating
   * though parents of parents, if they exist. Find the slug for each
   * parent and add it to the array.
   *
   * @param integer $parent_id
   * @return array
   */
  function parentSlug($parent_id) {
    $storage = \Drupal::entityManager()->getStorage('node');
    $parent_slug = [];
    if (!empty($parent_id)) {
      $next_parent = $parent_id;
      while (!empty($next_parent)) {
        $parent_node = $storage->load($next_parent);
        $next_parent = FALSE;
        if ($parent_node) {
          $next_slug = $parent_node->get('field_slug')->value;
          if (!empty($next_slug)) {
            $parent_slug[$next_slug] = (int) $parent_node->id();
          }
          $next_parent_id = $parent_node->get('field_parent')->target_id;
          if (!empty($next_parent_id)) {
            $next_parent = $next_parent_id;
          }
        }
      }
    }
    $parent_slug = array_flip(array_reverse($parent_slug));
    return $parent_slug;
  }

}
