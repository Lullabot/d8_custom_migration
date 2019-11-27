<?php

namespace Drupal\lullabot_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;

/**
 * Make sure a file can be loaded, which may require adding a dummy entry to
 * the file usage table.
 *
 * @MigrateProcessPlugin(
 *   id = "private_file_prepare"
 * )
 *
 * @code
 *  media_image_field/target_id:
 *    -
 *      plugin: migration_lookup
 *      migration: d7_file
 *      source: fid
 *    -
 *      plugin: private_file_prepare
 * @endcode
 */
class PrivateFilePrepare extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $value;

    if (empty($value))  {
      throw new MigrateException("Fid $value is empty.");
      return $value;
    }
    $file = \Drupal\file\Entity\File::load($value);
    if (!$file) {
      throw new MigrateException("Fid $value cannot be loaded.");
      $this->manuallyAddFileUsage($value);
    }

    return $value;
  }

  /**
   * Manually add entry to file usage table.
   *
   * If the file cannot be loaded, you can't use the FileUsage service,
   * which requires the loaded file as its argument, so instead just directly
   * do what that service does.
   */
  private function manuallyAddFileUsage($fid) {
    \Drupal\Core\Database\Connection::merge('file_usage')
      ->keys([
        'fid' => $fid,
        'module' => 'lullabot_migrate',
        'type' => 'file',
        'id' => $fid,
      ])
      ->fields(['count' => 1])
      ->expression('count', 'count + :count', [':count' => 1])
      ->execute();
  }

}
