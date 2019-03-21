<?php

namespace Drupal\lullabot_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Provides a 'LullabotDeck' migrate process plugin.
 *
 * Create Deck content if empty.
 *
 * @MigrateProcessPlugin(
 *  id = "lullabot_deck"
 * )
 */
class LullabotDeck extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $item = [];
    $deck = !empty($value['value']) ? $value['value'] : '';
    if (empty($deck)) {
      if (!empty($row->getSourceProperty('body/0/summary'))) {
        $deck = $row->getSourceProperty('body/0/summary');
      }
      else {
        $deck = strip_tags(text_summary($row->getSourceProperty('body/0/value')));
      }
    }
    $item = [
      'value' => $deck,
      'format' => '',
    ];
    return $item;
  }

}
