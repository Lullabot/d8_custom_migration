<?php

namespace Drupal\lullabot_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\file\Plugin\migrate\source\d7\File;

/**
 * Drupal 7 file source from database, limited types, dates and authors.
 *
 * Core plugin adapted to restrict imported files to only those in use on
 * specific node types, later than given dates.
 *
 * @MigrateSource(
 *   id = "d7_file_used",
 *   source_module = "file"
 * )
 */
class FileUsed extends File {
  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = parent::query();
    // Join the file_usage table.
    $query->leftJoin('file_usage', 'u', 'f.fid = u.fid');
    // Files used on nodes.
    $query->condition('u.type', 'node', '=');
    // Files actually in use.
    $query->condition('count', 0, '>');
    // Join the node table.
    $query->leftJoin('node', 'n', 'u.id = n.nid');
    // Files used on specific node types.
    $query->condition('n.type', MIGRATED_TYPES, 'IN');
    // Files later than earliest weather report.
    $query->condition('n.created', MIGRATED_EARLIEST, '>=');
    // Files authored by executive team.
    $query->condition('n.uid', MIGRATED_AUTHORS, 'IN');
    return $query;
  }
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = parent::getIds();
    // Add an alias since we're adding a join.
    $ids['fid']['alias'] = 'f';
    return $ids;
  }
}

