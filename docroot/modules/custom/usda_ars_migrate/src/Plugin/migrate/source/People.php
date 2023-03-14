<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\source;
use Drupal\Core\Database\Query\Condition;
use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;

/**
 * Defines People source plugin.
 *
 * @MigrateSource(
 *   id = "usda_ars_people",
 *   source_module = "usda_ars_migrate"
 * )
 */
class People extends UsdaArsSource {

    /**
     * {@inheritdoc}
     */
    public function query() {
        $status = ['A', 'NULL'];
        $query = $this->select('people', 'p');
        $query->fields('p');
        $or_condition = new Condition('OR');
        $or_condition->condition('p.Status', 'A');
        $or_condition->isNull('p.Status');
        $query->condition($or_condition);
        //$query->condition('p.PersonID', ['4429', '37269', '40006'], 'IN');
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function fields() {
        $fields = [
            'id' => $this->t('Person ID'),
            'title' => $this->t('Title'),
            'field_emp_id' => $this->t('Emp Id'),
            'field_perfname' => $this->t('Person First Name'),
            'field_permname' => $this->t('Person Middle Name'),
            'field_percommonname' => $this->t('Person Common Name'),
            'field_email' => $this->t('Person Email'),
            'field_homepageurl' => $this->t('Home Page Url'),
            'field_honorificname' => $this->t('Honorific Name'),
            'field_imageurl' => $this->t('Image Url'),
            'field_mode_code' => $this->t('Mode Code'),
            'field_mod_date' => $this->t('Mod Date'),
            'field_deskaddr' => $this->t('Desk Addr'),
            'field_deskbldgabbr' => $this->t('Desk Bldgaddr'),
            'field_deskext' => $this->t('Desk Ext'),
            'field_desk_phone' => $this->t('Desk Phone'),
            'field_deskroomnum' => $this->t('Desk Room Number'),
            'field_ofcaddress' => $this->t('Office Addr'),
            'field_ofcbldgabbr' => $this->t('Office Bldg'),
            'field_office_fax' => $this->t('Office Fax'),
            'field_ofcmailstop' => $this->t('Office Mail Stop'),
            'field_office_phone' => $this->t('Office Phone'),
            'field_ofcroomnum' => $this->t('Office Room Number'),
            'field_officialtitle' => $this->t('Official Ttitle'),
            'field_payscale' => $this->t('Pay Scale'),
            'field_pos_id' => $this->t('Post Id'),
            'field_p_emp_id' => $this->t('P Employe Id'),
            'field_seriesid' => $this->t('Series Id'),
            'field_status' => $this->t('Status'),
            'field_suffix' => $this->t('Suffix'),
            'field_workingtitle' => $this->t('Working Ttile'),
        ];
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getIds() {
        return [
            'PersonID' => [
                'type' => 'integer',
                'alias' => 'p',
            ],
        ];
    }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
    public function prepareRow(Row $row) {

      // The source properties can be added or modified in prepareRow().
      if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
        // The row has not been imported yet.
        // We have to check because the function prepareRow() called for each row
        // in each migration batch POST request.
        $mode_1 = $row->getSourceProperty('mode_1');
        $mode_2 = $row->getSourceProperty('mode_2');
        $mode_3 = $row->getSourceProperty('mode_3');
        $mode_4 = $row->getSourceProperty('mode_4');
        $mode_code = $mode_1 . "-" . $mode_2 . "-" . $mode_3 . "-" . $mode_4;
        $row->setSourceProperty('Mode_code', $mode_code);
      }
        return parent::prepareRow($row);
    }

    /**
     * {@inheritdoc}
     */
    public function entityTypeId() {
        return 'node';
    }

}
