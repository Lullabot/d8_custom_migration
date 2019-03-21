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
