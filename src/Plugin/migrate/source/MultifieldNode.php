<?php

namespace Drupal\lullabot_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Multifield Node source plugin.
 *
 * This will create one record for each multifield value on nodes of the
 * specified bundle, skipping nodes where the multifield is empty. Each row
 * will contain the basic node values plus the multifield values for a single
 * multifield record.
 *
 * Multifield values use the following pattern, and each one contains only one
 * column of the field value:
 *   {parent field name}_{child field name}_{column}.
 *
 * There is a unique id for each multifield that uses the following pattern,
 * and it is aliased in this plugin as 'mfid':
 *   {parent field name}_id
 *
 * Required configuration keys:
 * - field_name: The name of the parent multifield.
 * - node_type: The parent multifield node type.
 * Optional configuration keys:
 * - lookup_field: A field name that can be used in later migrations to look
 *     up values from the original migration. Must be the name of a field in
 *     the original multifield database table. Notrequired, if not provided
 *     the multifield id will be used as a lookup. Can be used to ensure that
 *     duplicate records get combined. For instance, if a title field is used
 *     and there are duplicate records with the same title, only a single
 *     record will be created, which can later use the title as a lookup value.
 *
 * Example usage to create a 'new_type' node for each 'field_multifield' value
 * on an 'article' where body and field_image are subfields on the
 * field_multifield multifield. Add this migration:
 *
 * id: my_multifield_new_type
 * source:
 *   plugin: multifield_node
 *   field_name: field_multifield
 *   lookup_field: field_multifield_field_title_value
 *   node_type: article
 * process:
 *   nid: field_multifield_field_title_value
 *   title: title
 *   uid: node_uid
 *   status: status
 *   created: created
 *   changed: changed
 *   body/value: body/value
 *   body/format: body/format
 *   field_image: field_image
 * destination:
 *   plugin: 'entity:node'
 *   default_bundle: new_type
 *
 * To reference each new node on the parent node, edit the original migration
 * to look up the newly created new_type node for each multifield item and
 * reference it, (assumes the new field has been created on the parent as an
 * entityreference field, and has been configured to reference 'content'):
 *
 * id: d7_node_article
 * process:
 *   field_multifield:
 *     plugin: sub_process
 *     source: field_multifield
 *     process:
 *       plugin: migration_lookup
 *       migration: my_multifield_new_type
 *       source_ids:
 *         - field_multifield_field_title_value
 * migration_dependencies:
 *   required:
 *     - my_multifield_new_type
 *
 * Based on the work done to migrate multifields to paragraphs:
 * @see https://www.drupal.org/project/paragraphs/issues/2977853
 *
 * Be aware that creating new nodes in the middle of a migration will create
 * nid conflicts with nodes migrated in later. The work around is to reset the
 * auto-increment tables before the migration, as we do in hook_install().
 * @see https://github.com/dcycle/d6_to_d8_migration_example/tree/7
 *
 * @MigrateSource(
 *   id = "multifield_node",
 *   source_module = "node"
 * )
 */
class MultifieldNode extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Add the Field API field values in case they might be useful on the new
    // entity.
    foreach (array_keys($this->getFields('node', $row->getSourceProperty('bundle'))) as $field) {
      $nid = $row->getSourceProperty('parent_nid');
      $vid = $row->getSourceProperty('parent_vid');
      $row->setSourceProperty($field, $this->getFieldValues('node', $field, $nid, $vid));
    }

    // Normalize the field structure so each multifield has the usual structure
    // in the source data instead of the odd structure used by multifield.
    // This makes it possible to use normal migrate process plugins on the
    // values.
    $item = [];
    $field_name = $this->configuration['field_name'];
    foreach ($row->getSource() as $key => $source_field) {
      if (strpos($key, $field_name) !== FALSE) {
        $subfield = $field_name . '_' . str_replace($field_name . '_', '', $key);
        if ($subfield != $field_name . '_id') {
          $parts = explode('_', $subfield);
          $suffix = array_pop($parts);
          $subfield = str_replace('_' . $suffix, '', $subfield);
          $item[$subfield][$suffix] = $row->getSourceProperty($key);
        }
        else {
          $item[$subfield] = $row->getSourceProperty($key);
        }
      }
    }
    // Store the normalized value, so we have both the original, complex,
    // field name and value and this normalized version. So either can be used
    // in the migration processing.
    foreach ($item as $subfield => $value) {
      $row->setSourceProperty($subfield, [$value]);
    }

    parent::prepareRow($row);

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'field_name' => '',
        'lookup_field' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {

    // Query the field that contains data for this multifield.
    $table = 'field_data_' . $this->configuration['field_name'];
    $query = $this->select($table, 'mf')
      ->condition('deleted', 0)
      ->fields('mf');

    // Join in the parent node data, re-naming values so the migration
    // to avoid confusion between the new node being created and the original.
    $query->innerJoin('node', 'n', 'n.nid=mf.entity_id');
    $query->addField('n', 'nid', 'parent_nid');
    $query->addField('n', 'vid', 'parent_vid');
    $query->addField('n', 'title', 'parent_title');
    $query->addField('n', 'type', 'parent_type');
    $query->addField('n', 'created', 'parent_created');
    $query->addField('n', 'changed', 'parent_changed');
    $query->addField('n', 'uid', 'parent_uid');
    $query->addField('n', 'language', 'parent_language');
    $query->addField('n', 'status', 'parent_status');

    // Order by creation date, ascending, so if there are duplicate records,
    // the later ones overwrite the earlier ones.
    $query->orderBy('n.created', 'ASC');

    // Create a 'mfid' alias for the id field to make it easy to reference;
    $query->addField('mf', $this->configuration['field_name'] . '_id', 'mfid');
    if ($lookup_field = $this->configuration['lookup_field']) {
      $query->isNotNull('mf.'. $lookup_field);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields['mfid'] = $this->t('Multifield id');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $lookup_field = $this->configuration['lookup_field'];
    if (!empty($lookup_field)) {
      $ids[$lookup_field]['type'] = 'string';
      $ids[$lookup_field]['alias'] = 'mf';
    }
    else {
      $ids['mfid']['type'] = 'integer';
      $ids['mfid']['alias'] = 'mf';
    }
    return $ids;
  }

}
