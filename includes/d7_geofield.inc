<?php
/**
 * @file
 *   Code to convert D7 geofield to D8 geofields.
 *
 * Problem being solved: There is no migration for D7 to D8 geofields.
 */
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;

function custom_migration_geofield(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {

  // List all the geofield field names.
  $geofields = array(
    'field_geofield',
  );

  // The field type doesn't change, so this could be skipped.
  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_field') {
    $process = $migration->getProcess();
    $process['type'][0]['map']['geofield'] = 'geofield';
    $migration->setProcess($process);
  }

  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_field_instance_widget_settings') {

    // Map the changes in widget names. This does not update widget settings.
    // They have to be updated manually after migration.
    $widget_map = array(
      'geofield_wkt' => 'geofield_default',
      'geofield_geojson' => 'geofield_default',
      'geofield_latlon' => 'geofield_latlon',
      'geofield_bounds' => 'geofield_Bounds',
      'geofield_textfields' => 'geofield_default',
      'geofield_geolocation' => 'geofield_default',
      'geofield_openlayers' => 'geofield_default',
      'geocoder' => 'geofield_default',
    );

    if ($row->getSourceProperty('type') == 'geofield') {
      $process = $migration->getProcess();
      $process['options/type']['type']['map'] += $widget_map;
      $migration->setProcess($process);
    }
  }

  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_field_formatter_settings') {

    // Map the changes in formatter names. This does not update formatter settings.
    // They have to be updated manually after migration.
    $formatter_map = array(
      'geofield_wkt' => 'geofield_default',
      'geofield_geojson' => 'geofield_default',
      'geofield_kml' => 'geofield_default',
      'geofield_gpx' => 'geofield_default',
      'geofield_latlon' => 'geofield_default',
      'geofield_lat' => 'geofield_default',
      'geofield_lon' => 'geofield_default',
      'geofield_geo_type' => 'geofield_default',
      'geofield_openlayers' => 'geofield_default',
      'geofield_def_list' => 'geofield_default',
      'geofield_description' => 'geofield_default',
      'geofield_map_map' => 'geofield_default',
    );

    if (substr($row->getSourceProperty('type'), 0, 8) == 'geofield') {
      $process = $migration->getProcess();
      $process['options/type'][0]['map'] += $formatter_map;
      $migration->setProcess($process);
    }
  }

  // Update all the entity migrations that have geo fields, on this site it's only nodes.
  // This will pick up both the main and the revision migrations.
  $len = strlen('d7_node__') + strlen(CUSTOM_MIGRATION_PREFIX);
  if (substr($migration->id(), 0, $len) == CUSTOM_MIGRATION_PREFIX . 'd7_node__') {
    foreach ($geofields as $field) {
      if ($value = $row->getSourceProperty($field)) {
        if (!empty($value)) {
          // New value field replaces wkt from 7.1 version of geofield.
          // This code would be different if migrating from 7.2.
          geophp_load();
          foreach($value as $delta => $item) {
            $geom = geoPHP::load($value[$delta]['wkt'], 'wkt');
            $value[$delta]['value'] = $geom->out('wkb');
          }
          $row->setSourceProperty($field, $value);
          // Uncomment to debug results.
          //drush_print_r($row);
        }
      }
    }

  }
}