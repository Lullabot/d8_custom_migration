<?php

namespace Drupal\lullabot_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Provides a 'LookupEpisodeGuests' migrate process plugin.
 *
 * Looks up episode guests from Bio and External Bio migrations.
 *
 * @MigrateProcessPlugin(
 *  id = "lookup_episode_guests"
 * )
 */
class LookupEpisodeGuests extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $item = [];
    $migration_ids = [
      'd7_multifield_episode_guests',
      'd7_node_bio',
    ];
    $migrationPluginManager = \Drupal::service('plugin.manager.migration');
    $migrations = $migrationPluginManager->createInstances($migration_ids);
    if (!empty($value['field_episode_guests_bot_target_id'])) {
      $migration = $migrations['d7_node_bio'];
      $target_ids = $migration->getIdMap()->lookupDestinationIds([$value['field_episode_guests_bot_target_id']]);
      $item['target_id'] = reset(reset($target_ids));
    }
    elseif (!empty($value['field_episode_guests_name_value'])) {
      $migration = $migrations['d7_multifield_episode_guests'];
      $target_ids = $migration->getIdMap()->lookupDestinationIds([$value['field_episode_guests_name_value']]);
      $item['target_id'] = reset(reset($target_ids));
    }
    return $item;
  }

}
