<?php
namespace Drupal\lullabot_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\lullabot_migrate\Plugin\migrate\source\FileUsed;

/**
 * Drupal 7 file source from database, limited types, dates and authors.
 *
 * Core plugin adapted to restrict imported files to only those in use on
 * specific node types, later than given dates.
 *
 * @MigrateSource(
 *   id = "d7_file_media_audio",
 *   source_module = "file"
 * )
 */
class FileMediaAudio extends FileUsed {
  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = parent::query();
    $query->condition('f.filemime', 'audio/%', 'LIKE');
    return $query;
  }

}
