<?php

use Drupal\paragraphs\Entity\Paragraph;

class LullabotPages() {

  function createParagraphs() {

    // Create home page.
    $node = [
      'title' => "Hi, we're Lullabot.",
      'type' => 'section',
      'status' => 1,
      'field_lead' => [
        0 => [
          'value' => "Strategy, design, and Drupal development for large-scale publishers.",
        ],
      ],
      'field_primary_cta' => [
        0 => [
          'uri' => 'entity:node/42'
          'title' => "Let's connect"
          'options' => [],
        ],
      ],
    ];

    // Create Home page paragraphs.
    $paragraph = Paragraph::create(['type' => 'custom_promo']);
    $paragraph->set('field_title', "Edutopia");
    $paragraph->set('field_subtitle', "Building a New Edutopia on Decoupled Drupal 8");
    $file = file_load(4739);
    $paragraph->set('field_image', $file);
    $content = [
      'uri' => "entity:node/1699".
      'title' => "",
    ]
    $paragraph->set('field_promo_link', $content);
    $paragraph->isNew();
    $paragraph->save();
    $node->field_content_sections->appendItem($paragraph);

    $paragraph = Paragraph::create(['type' => 'view']);
    $content = [
      'target_id' => "random_quote".
      'display_id' => "block_1".
      'data' => 'a:5:{s:5:"pager";s:4:"some";s:8:"argument";s:0:"";s:5:"limit";s:1:"1";s:6:"offset";s:0:"";s:5:"title";i:0;}',
    ]
    $paragraph->set('field_view', $content);
    $paragraph->isNew();
    $paragraph->save();
    $node->field_content_sections->appendItem($paragraph);

  }
}
