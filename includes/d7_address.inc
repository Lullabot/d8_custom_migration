<?php
/**
 * @file
 *   Code to convert D7 addressfield fields to D8 address fields.
 *
 * Problem being solved: There is no migration path for D7 addressfields to map them to D8 address.
 */
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;

function custom_migration_address(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {

  // List all the address field names.
  $address_fields = array(
    'field_address',
  );

  // Convert addressfield fields into address fields.
  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_field') {
    $process = $migration->getProcess();
    $process['type'][0]['map']['addressfield'] = 'address';
    $migration->setProcess($process);
  }

  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_field_instance_widget_settings') {

    // Map the changes in widget names. This does not update widget settings.
    // They have to be updated manually after migration.
    $widget_map = array(
      'addressfield_standard' => 'address_default',
    );

    if ($row->getSourceProperty('type') == 'addressfield') {
      $process = $migration->getProcess();
      $process['options/type']['type']['map'] += $widget_map;
      $migration->setProcess($process);
    }
  }

  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_field_formatter_settings') {

    // Map the changes in formatter names. This does not update formatter settings.
    // They have to be updated manually after migration.
    $formatter_map = array(
      'addressfield_default' => 'address_default',
    );

    if (substr($row->getSourceProperty('type'), 0, 12) == 'addressfield') {
      $process = $migration->getProcess();
      $process['options/type'][0]['map'] += $formatter_map;
      $migration->setProcess($process);
    }
  }

  // Update all the entity migrations that have address fields, on this site it's only nodes.
  // This will pick up both the main and the revision migrations.
  $len = strlen('d7_node__') + strlen(CUSTOM_MIGRATION_PREFIX);
  if (substr($migration->id(), 0, $len) == CUSTOM_MIGRATION_PREFIX . 'd7_node__') {

    foreach ($address_fields as $field) {
      if ($value = $row->getSourceProperty($field)) {
        if (!empty($value)) {
          foreach($value as $delta => $item) {
            // Previous country is now called country_code
            $value[$delta]['country_code'] = $value[$delta]['country'];
            unset($value[$delta]['country']);
            // Previous thoroughfare is now address_line1
            $value[$delta]['address_line1'] = $value[$delta]['thoroughfare'];
            unset($value[$delta]['thoroughfare']);
            // Administrative area changed from WI to US-WI
            $value[$delta]['administrative_area'] = $value[$delta]['country_code'] . '-' . $value[$delta]['administrative_area'];
            // Previous organisation is now organisation_name
            $value[$delta]['organization'] = $value[$delta]['organisation_name'];
            unset($value[$delta]['organisation_name']);
            // New 'recipient' field seems to replace several previous values, munge them together.
            $value[$delta]['recipient'] = trim($value[$delta]['name_line'] . ' ' .  $value[$delta]['first_name'] . ' ' .  $value[$delta]['last_name']);
            unset($value[$delta]['name_line'], $value[$delta]['first_name'], $value[$delta]['last_name']);
            // Recipent field is required, so if it's empty after that, use the node title.
            if (empty($value[$delta]['recipient'])) {
              $value[$delta]['recipient'] = $row->getSourceProperty('title');
            }
          }
          $row->setSourceProperty($field, $value);
          // Uncomment to debug results.
          //drush_print_r($row);
        }
      }
    }
  }
}