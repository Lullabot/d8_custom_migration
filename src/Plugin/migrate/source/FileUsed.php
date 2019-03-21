<?php

namespace Drupal\lullabot_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\file\Plugin\migrate\source\d7\File;

/**
 * Drupal 7 file source from database.
 *
 * Core plugin adapted to restrict imported files to only those in use.
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

    // Join the file_usage table and restrict the query to fields actually
    // in use.
    $query->leftJoin('file_usage', 'u', 'f.fid = u.fid');
    $query->condition('count', 0, '>');

    // Join the title and alt text fields.
    //$query->leftJoin('field_data_field_file_image_alt_text', 'alt', 'f.fid = alt.entity_id');
    //$query->addField('alt', 'field_file_image_alt_text_value', 'alt');
    //$query->leftJoin('field_data_field_file_image_title_text', 'title', 'f.fid = title.entity_id');
    //$query->addField('title', 'field_data_field_file_image_title_text_value', 'title');

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
