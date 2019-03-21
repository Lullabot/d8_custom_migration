<?php

namespace Drupal\lullabot_migrate;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;

/**
 * Provides a 'LullabotMigrationService' service.
 *
 * Helper for migration and management of D7 database data.
 */
class LullabotMigrationService extends ServiceProviderBase {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Drupal 8 database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs an LullabotMigrationService instance.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Core messager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object.
   */
  public function __construct(
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection
    ) {
    $this->messenger = $messenger;
    $this->logger = $logger_factory->get('lullabot_migrate_media');
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
  }

  /**
   * Check database connectivity.
   */
  public function hasD7Connection() {
    return FALSE;
  }

  /**
   * Find D7 and D8 node ids that don't match.
   *
   * @param string $d7_content_type
   *   The D7 content type to search.
   * @param string $d8_content_type
   *   The D8 content type to search.
   *
   * @return array
   *   An array of ids that differ between D7 and D8.
   */
  public function nodeDifference($d7_content_type, $d8_content_type) {

    $query = $this->connection->select('node', 'n');
    $query->fields('n', ['nid']);
    $query->condition('type', $d7_content_type);
    $d7_values = $query->execute()->fetchCol();

    $query = $this->connection->select('node', 'n');
    $query->fields('n', ['nid']);
    $query->condition('type', $d8_content_type);
    $d8_values = $query->execute()->fetchCol();

    return(array_diff($d7_values, $d8_values));

  }

  /**
   * List ids of items that failed a migration.
   *
   * @return array
   *   Returns all items in the migration that are missing a destid.
   */
  public function failedMigration($migration) {
    $query = $this->connection->select('migrate_map_' . $migration, 'm')
      ->fields('m', ['sourceid1'])
      ->condition('m.destid1', NULL, 'IS NULL');
    $values = $query->execute()->fetchAll();
    return $values;
  }

  /**
   * Update langcode for all previously-migrated content.
   *
   * Needed because new content uses 'en' instead of 'und', and we want
   * consistency between new and old. This has been changed in the migration
   * but won't affect previously-migrated content without a total rollback.
   * This can be used post-migration to ensure all content, no matter when
   * migrated, has the new value.
   *
   * @return array
   *   Returns an associative array keyed by table name with the number of
   *   records updated in each table.
   */
  public function updateLangcode() {
    $results = [];
    $tables = [
      'node',
      'node__body',
      'node__field_assets_media',
      'node__field_audio_media',
      'node__field_authors',
      'node__field_bio_active',
      'node__field_bio_expertise',
      'node__field_bio_external',
      'node__field_bio_fact',
      'node__field_bio_gender_pronoun',
      'node__field_bio_link_dribbble',
      'node__field_bio_link_drupalorg',
      'node__field_bio_link_facebook',
      'node__field_bio_link_flickr',
      'node__field_bio_link_github',
      'node__field_bio_link_googleplus',
      'node__field_bio_link_lanyrd',
      'node__field_bio_link_linkedin',
      'node__field_bio_link_twitter',
      'node__field_bio_location',
      'node__field_bio_map_media',
      'node__field_bio_media',
      'node__field_bio_position',
      'node__field_bio_promos',
      'node__field_bio_quote_cite',
      'node__field_bio_quote_text',
      'node__field_bio_time_zone',
      'node__field_case_study_site_link',
      'node__field_colour_scheme_primary',
      'node__field_company',
      'node__field_content_sections',
      'node__field_deck',
      'node__field_display_title',
      'node__field_drupal_planet',
      'node__field_episode_explicit',
      'node__field_episode_guests',
      'node__field_episode_number',
      'node__field_episode_quote',
      'node__field_expertise_type',
      'node__field_external_link',
      'node__field_external_source',
      'node__field_google_play_url',
      'node__field_hero_image_invert',
      'node__field_hero_media',
      'node__field_items',
      'node__field_itunes_categories',
      'node__field_itunes_owner',
      'node__field_itunes_owner_email',
      'node__field_itunes_summary',
      'node__field_itunes_url',
      'node__field_lead',
      'node__field_legacy_nid',
      'node__field_links',
      'node__field_parent',
      'node__field_pdf_media',
      'node__field_position',
      'node__field_primary_cta',
      'node__field_promo_image_invert',
      'node__field_promo_media',
      'node__field_promo_text',
      'node__field_quotation',
      'node__field_quotation_citation',
      'node__field_quotation_role',
      'node__field_related_links',
      'node__field_rss_feed_url',
      'node__field_secondary_cta',
      'node__field_show_background_color',
      'node__field_show_hero_background_image',
      'node__field_show_media',
      'node__field_show_retired',
      'node__field_slug',
      'node__field_sort_weight',
      'node__field_source',
      'node__field_spotify_url',
      'node__field_stitcher_url',
      'node__field_topics',
      'node__field_transcript',
      'node__field_type',
      'node__field_webinar_date',
      'node__field_webinar_duration_mins',
      'node__field_webinar_link',
      'node__field_webinar_registration_link',
      'node__layout_builder__layout',
      'node_field_data',
      'node_field_revision',
      'node_revision',
      'node_revision__body',
      'node_revision__field_assets_media',
      'node_revision__field_audio_media',
      'node_revision__field_authors',
      'node_revision__field_bio_active',
      'node_revision__field_bio_expertise',
      'node_revision__field_bio_external',
      'node_revision__field_bio_fact',
      'node_revision__field_bio_gender_pronoun',
      'node_revision__field_bio_link_dribbble',
      'node_revision__field_bio_link_drupalorg',
      'node_revision__field_bio_link_facebook',
      'node_revision__field_bio_link_flickr',
      'node_revision__field_bio_link_github',
      'node_revision__field_bio_link_googleplus',
      'node_revision__field_bio_link_lanyrd',
      'node_revision__field_bio_link_linkedin',
      'node_revision__field_bio_link_twitter',
      'node_revision__field_bio_location',
      'node_revision__field_bio_map_media',
      'node_revision__field_bio_media',
      'node_revision__field_bio_position',
      'node_revision__field_bio_promos',
      'node_revision__field_bio_quote_cite',
      'node_revision__field_bio_quote_text',
      'node_revision__field_bio_time_zone',
      'node_revision__field_case_study_site_link',
      'node_revision__field_colour_scheme_primary',
      'node_revision__field_company',
      'node_revision__field_content_sections',
      'node_revision__field_deck',
      'node_revision__field_display_title',
      'node_revision__field_drupal_planet',
      'node_revision__field_episode_explicit',
      'node_revision__field_episode_guests',
      'node_revision__field_episode_number',
      'node_revision__field_episode_quote',
      'node_revision__field_expertise_type',
      'node_revision__field_external_link',
      'node_revision__field_external_source',
      'node_revision__field_google_play_url',
      'node_revision__field_hero_image_invert',
      'node_revision__field_hero_media',
      'node_revision__field_items',
      'node_revision__field_itunes_categories',
      'node_revision__field_itunes_owner',
      'node_revision__field_itunes_owner_email',
      'node_revision__field_itunes_summary',
      'node_revision__field_itunes_url',
      'node_revision__field_lead',
      'node_revision__field_legacy_nid',
      'node_revision__field_links',
      'node_revision__field_parent',
      'node_revision__field_pdf_media',
      'node_revision__field_position',
      'node_revision__field_primary_cta',
      'node_revision__field_promo_image_invert',
      'node_revision__field_promo_media',
      'node_revision__field_promo_text',
      'node_revision__field_quotation',
      'node_revision__field_quotation_citation',
      'node_revision__field_quotation_role',
      'node_revision__field_related_links',
      'node_revision__field_rss_feed_url',
      'node_revision__field_secondary_cta',
      'node_revision__field_show_background_color',
      'node_revision__field_show_hero_background_image',
      'node_revision__field_show_media',
      'node_revision__field_show_retired',
      'node_revision__field_slug',
      'node_revision__field_sort_weight',
      'node_revision__field_source',
      'node_revision__field_spotify_url',
      'node_revision__field_stitcher_url',
      'node_revision__field_topics',
      'node_revision__field_transcript',
      'node_revision__field_type',
      'node_revision__field_webinar_date',
      'node_revision__field_webinar_duration_mins',
      'node_revision__field_webinar_link',
      'node_revision__field_webinar_registration_link',
      'node_revision__layout_builder__layout',
      'url_alias',
    ];
    foreach ($tables as $table) {
      $query = $this->connection->update($table)
        ->fields(['langcode' => 'en']);
      $results[$table] = $query->execute();
    }
    drupal_flush_all_caches();
    return $results;
  }

}
