<?php

/**
 * @file
 * Contains \Drupal\custom_migration\Plugin\migrate\source\Book.
 *
 * Fixes missing book structure, from https://www.drupal.org/node/2409435.
 * Remove this file and corresponding book yaml files if that patch gets
 * applied to core.
 */

namespace Drupal\custom_migration\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal book source from database.
 *
 * @MigrateSource(
 *   id = "book",
 *   source_provider = "book"
 * )
 */
class Book extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('book', 'b')->fields('b', array('nid', 'bid'));
    $query->join('menu_links', 'ml', 'b.mlid = ml.mlid');
    $ml_fields = array('mlid', 'plid', 'weight', 'has_children', 'depth');
    for ($i = 1; $i <= 9; $i++) {
      $field = "p$i";
      $ml_fields[] = $field;
      $query->orderBy($field);
    }
    $query->fields('ml', $ml_fields);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['mlid']['type'] = 'integer';
    $ids['mlid']['alias'] = 'ml';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'nid' => $this->t('Node ID'),
      'bid' => $this->t('Book ID'),
      'mlid' => $this->t('Menu link ID'),
      'plid' => $this->t('Parent link ID'),
      'weight' => $this->t('Weight'),
      'p1' => $this->t('The first mlid in the materialized path.'),
      'p2' => $this->t('The second mlid in the materialized path.'),
      'p3' => $this->t('The third mlid in the materialized path.'),
      'p4' => $this->t('The fourth mlid in the materialized path.'),
      'p5' => $this->t('The fifth mlid in the materialized path.'),
      'p6' => $this->t('The sixth mlid in the materialized path.'),
      'p7' => $this->t('The seventh mlid in the materialized path.'),
      'p8' => $this->t('The eight mlid in the materialized path.'),
      'p9' => $this->t('The nine mlid in the materialized path.'),
    );
  }

}
