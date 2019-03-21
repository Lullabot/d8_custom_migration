<?php

namespace Drupal\lullabot_migrate\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Drupal 7 node source from database with uuid added and adjusting pulling
 * latest vid by timestamp rather than relying on the vid in the node table.
 * It turns out the vid in the node table does not always match the revision
 * published on LB.com but the latest revision by timestamp (even if Drupal
 * thinks it is unpublished) does.
 *
 * @MigrateSource(
 *   id = "d7_node_uuid_revision",
 *   source_module = "node"
 * )
 */
class NodeUuidRevision extends Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n', [
        'nid',
        'type',
        'language',
        'status',
        'created',
        //'changed',
        'comment',
        'promote',
        'sticky',
        'tnid',
        'translate',
      ])
      ->fields('nr', [
        'vid',
        'title',
        'log',
        'timestamp',
      ]);

    $query->addField('n', 'uid', 'node_uid');
    $query->addField('nr', 'nid', 'nr_nid');
    $query->addField('nr', 'uid', 'revision_uid');
    $query->addField('nr', 'timestamp', 'changed');
    $query->addField('n', 'uuid');

    $subquery = $this->select('node_revision', 'nr')
      ->fields('nr', [
        'nid',
        'vid',
        'title',
        'log',
        'timestamp',
        'uid',
      ]);

    // Subquery to limit revisions to the latest based on timestamp, not vid.
    $subquery3 = $this->select('node_revision', 'nr3');
    $subquery3->addField('nr3', 'nid');
    $subquery3->addExpression('MAX(timestamp)', 'timestamp');
    $subquery3->groupBy('nid');

    // To limit this to published nodes only, uncomment.
    //$subquery3->condition('status', 1);

    $subquery->innerJoin($subquery3, 'nr3', 'nr.nid=nr3.nid AND nr.timestamp=nr3.timestamp');

    // Join on nid not vid since the vid in the node table is not always right.
    $query->innerJoin($subquery, 'nr', 'n.nid=nr.nid');

    // If the content_translation module is enabled, get the source langcode
    // to fill the content_translation_source field.
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $query->leftJoin('node', 'nt', 'n.tnid = nt.nid');
      $query->addField('nt', 'language', 'source_langcode');
    }
    $this->handleTranslations($query);

    if (isset($this->configuration['node_type'])) {
      $query->condition('n.type', $this->configuration['node_type']);
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
