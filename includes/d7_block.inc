<?php

/**
 * @file
 *   Code to limit the migrated blocks to only the ones used by the active theme in the source.
 *
 * Problem being solved: A legacy site that has been through many upgrades and used a lot of themes has
 * hundreds of blocks that are all getting created in the new D8 site. It's time to stop the madness!
 */
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;

function custom_migration_block(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {

  // Don't try to migrate all the old blocks. Find the ones for the previous active
  // theme and switch to new active theme. This avoids pulling in dozens or hundreds
  // of blocks for unused themes in the source. This also avoids problems when the D8
  // themes don't have the same names as the source themes (where you will get dozens of
  // blocks but none that are set up for the new theme).

  $new_theme = \Drupal::config('system.theme')->get('default');
  $new_admin_theme = \Drupal::config('system.theme')->get('admin');

  $skip_modules = array(
    // More D7 modules that won't be used in this site for D8,
    // or where we will manually place the D8 version of their blocks.
    'views',
    'taxonomy_filter',
    'tagadelic_taxonomy',
    'tagadelic',
    'site_map',
    'profile',
    'lexicon',
    'diff',
    'devel',
    'context_ui',
    'adsense_oldcode',
    'adsense_managed',
  );

  if ($migration->id() == CUSTOM_MIGRATION_PREFIX . 'd7_block') {

    // Don't automatically migrate all blocks.
    if (!in_array($row->getSourceProperty('module'), $skip_modules)) {
      // This is a block in the source's active theme.
      if ($row->getSourceProperty('theme') == $row->getSourceProperty('default_theme')) {
        $process = $migration->getProcess();
        //drush_print_r($process);
        $row->setSourceProperty('theme', $new_theme);
        $row->setSourceProperty('default_theme', $new_theme);
        $row->setSourceProperty('admin_theme', $new_admin_theme);
        drush_print_r('KEEP: block.block.' . $row->getSourceProperty('theme') . '.' . $row->getSourceProperty('module') . '.' . $row->getSourceProperty('delta'));
      }
      // This is a block from some other theme.
      else {
        //drush_print_r($row);
        $process = $migration->getProcess();
        unset($process['plugin'][0]);
        unset($process['plugin'][1]);
        $process['plugin'][2]['source'] = 'visibility';
        $migration->setProcess($process);
        return FALSE;
      }
    }
    // This is a block from a module we want to skip in D8.
    else {
      $process = $migration->getProcess();
      unset($process['plugin'][0]);
      unset($process['plugin'][1]);
      $process['plugin'][2]['source'] = 'visibility';
      $migration->setProcess($process);
      return FALSE;
    }
  }
}