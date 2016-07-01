<?php
/**
 * @file
 *   Code to convert D7 link fields to D8 link fields.
 */
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;

function custom_migration_link(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {

  // The field type doesn't change so this could be skipped.
  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_field') {
    $process = $migration->getProcess();
    $process['type'][0]['map']['link'] = 'link';
    $migration->setProcess($process);
  }

  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_field_instance_widget_settings') {

    // Map the changes in widget names. This does not update widget settings.
    // They have to be updated manually after migration.
    $widget_map = array(
      'link_field' => 'link_default',
    );

    if ($row->getSourceProperty('type') == 'link') {
      $process = $migration->getProcess();
      $process['options/type']['type']['map'] += $widget_map;
      $migration->setProcess($process);
    }
  }

  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_field_formatter_settings') {

    // Map the changes in formatter names. This does not update formatter settings.
    // They have to be updated manually after migration.
    $formatter_map = array(
      'link_default' => 'link_default',
      'link_title_plain' => 'link_default',
      'link_host' => 'link_default',
      'link_url' => 'link_default',
      'link_plain' => 'link_default',
      'link_absolute' => 'link_default',
      'link_domain' => 'link_default',
      'link_short' => 'link_default',
      'link_label' => 'link_default',
      'link_separate' => 'link_separate',
      'link_hidden' => 'hidden',
    );

    if (substr($row->getSourceProperty('type'), 0, 4) == 'link') {
      $process = $migration->getProcess();
      $process['options/type'][0]['map'] += $formatter_map;
      $migration->setProcess($process);
    }
  }

  // Update all the entity migrations that have link fields, on this site it's only nodes.
  // This will pick up both the main and the revision migrations.
  $len = strlen('d7_node__') + strlen(CUSTOM_MIGRATION_PREFIX);
  if (substr($migration->id(), 0, $len) == CUSTOM_MIGRATION_PREFIX . 'd7_node__') {

    // List all the link field names.
    $link_fields = array(
      'field_webpage',
    );
    foreach ($link_fields as $field) {
      if ($value = $row->getSourceProperty($field)) {
        if (!empty($value)) {
          foreach($value as $delta => $item) {
            // Now uses 'uri' instead of 'url'
            $value[$delta]['uri'] = $value[$delta]['url'];
            unset($value[$delta]['url']);
          }
          $row->setSourceProperty($field, $value);
          // Uncomment to debug results.
          //drush_print_r($row);
        }
      }
    }
  }
}
