<?php

namespace Drupal\lullabot_migrate\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Drupal 7 node source from database with uuid added.
 *
 * @MigrateSource(
 *   id = "d7_node_uuid",
 *   source_module = "node"
 * )
 */
class NodeUuid extends Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->addField('n', 'uuid');
    // Nodes belonging to migrated types.
    $query->condition('n.type', MIGRATED_TYPES, 'IN');

    // Blog nodes later than given date.
    if (isset($this->configuration['node_type']) && $this->configuration['node_type'] == 'blog') {
      $query->condition('n.created', MIGRATED_EARLIEST, '>=');
      // Nodes authored by executive team.
      $query->condition('n.uid', MIGRATED_AUTHORS, 'IN');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['uuid'] = $this->t('Uuid');
    return $fields;
  }

}
