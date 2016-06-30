<?php

/**
 * @file
 *   Code to convert legacy numeric format names to D8 names.
 */
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Entity\MigrationInterface;

function custom_migration_filter(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {

  // Map the old numeric format names to new D8 formats.
  // The out-of-the-box migration would retain the D7 format names.
  // This assumes all necessary formats have already been created in D8 by the D8 installation process.
  $format_map = array(
    'js' => '',
    'ds_code' => '',
    1 => 'basic_html', // Filtered HTML in D7.
    2 => '', // PHP in D7
    3 => 'full_html', // Full HTML in D7.
    4 => 'plain_text', // Plain Text in D7.
  );

  // Switch to static map instead of straight pass-through.
  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_filter_format') {
    $process = $migration->getProcess();
    if (array_key_exists('format', $process)) {
      $process['format'][0]['plugin'] = 'static_map';
      $process['format'][0]['map'] = $format_map;
      $migration->setProcess($process);
    }
  }

  // Set custom blocks to use the new D8 format.
  // They should be picking it up from the filter migration as adjusted above,
  // but that isn't working, so just set the right value.
  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_custom_block') {
    $value = $row->getSourceProperty('format');
    $row->setSourceProperty('format', $format_map[$value]);
    $process = $migration->getProcess();
    $process['body/format'][0]['plugin'] = 'get';
    $migration->setProcess($process);
  }

  // Update body and formatted text fields to use the new D8 formats.
  $text_fields = array(
    'body',
    'field_description',
    'field_gallery_image_original',
  );
  // Update nodes to use the new D8 formats.
  $len = strlen('d7_node__') + strlen(CUSTOM_MIGRATION_PREFIX);
  if (substr($migration->id(), 0, $len) == CUSTOM_MIGRATION_PREFIX . 'd7_node__') {
    foreach ($text_fields as $text_field) {
      $value = $row->getSourceProperty($text_field);
      if (!empty($value) && $format = $value[0]['format']) {
        $value[0]['format'] = $format_map[$format];
        $row->setSourceProperty($text_field, $value);
      }
    }
  }

  // Update taxonomy term description to use the new D8 formats.
  if (substr($migration->id(), 0, 16) == CUSTOM_MIGRATION_PREFIX . 'd7_taxonomy_term') {
    $value = $row->getSourceProperty('description');
    $format = $row->getSourceProperty('format');
    if (!empty($value) && !empty($format)) {
      $row->setSourceProperty('format', $format_map[$format]);
    }
    elseif (!empty($value) && empty($format)) {
      $row->setSourceProperty('format', $format_map[1]);
    }
  }

}