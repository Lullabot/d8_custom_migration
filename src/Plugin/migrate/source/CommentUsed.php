<?php

namespace Drupal\lullabot_migrate\Plugin\migrate\source;

use Drupal\comment\Plugin\migrate\source\d7\Comment;

/**
 * Drupal 7 comment source from database, limited types, dates and authors.
 *
 * @MigrateSource(
 *   id = "d7_comment_used",
 *   source_module = "comment"
 * )
 */
class CommentUsed extends Comment {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('comment', 'c')->fields('c');
    $query->innerJoin('node', 'n', 'c.nid = n.nid');
    // Comments belonging to migrated types.
    $query->condition('n.type', MIGRATED_TYPES, 'IN');
    // Comments on nodes later than earliest weather report.
    $query->condition('n.created', MIGRATED_EARLIEST, '>=');
    // Comments on nodes authored by executive team.
    $query->condition('n.uid', MIGRATED_AUTHORS, 'IN');
    $query->addField('n', 'type', 'node_type');
    $query->orderBy('c.created');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['cid']['type'] = 'integer';
    return $ids;
  }

}
