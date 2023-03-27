<?php
/**
 * @file
 * Tests DB connection to MSSQL DB.
 */

namespace Drupal\usda_aris_public_data\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;

/**
 * Class MssqlTestController.
 *
 * @package Drupal\usda_ars_migrate\Controller
 */
class MssqlTestController extends ControllerBase {

  /**
   * Establishes DB connection to MSSQL DB and performs SELECT query.
   * Displays the Most Recent 10 People page.
   */
  public function testConnection() {
    try {
      $query = Database::getConnection('default', 'aris_public_web')
        ->select('people', 'p')
        ->fields('p', ['PersonID', 'PerFName', 'PerLName', 'WorkingTitle']);
      $or_condition = new Condition('OR');
      $or_condition->condition('p.Status', 'A');
      $or_condition->isNull('p.Status');
      $query->condition($or_condition);
      $query->range(0, 10);
      $query->orderBy('PersonID', 'DESC');
      $result = $query->execute();

      // Extract the information from the query result.
      $items = array();
      foreach ($result as $row) {
        $personId = $row->PersonID;
        $name = $row->PerFName . ' ' . $row->PerLName;
        $title = $row->WorkingTitle;
        $items[] = [
          'personId' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Person ID: ' . $personId,
          ],
          'name' => [
            '#type' => 'html_tag',
            '#tag' => 'h1',
            '#value' => $name,
          ],
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $title,
          ],
        ];
      }

      // Make the render array for a paged list.
      $build = array();
      $build['items'] = array(
        '#theme' => 'item_list',
        '#items' => $items,
      );
      // Add the pager.
      $build['item_pager'] = array('#type' => 'pager');

      return $build;
    }
    catch (\Exception $e) {
      \Drupal::logger('usda_ars_drupal')->error('Error connecting to MSSQL database aris_public_web. Error code: @code, @message.',
        array(
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ));
      dpm($e);
    }
    return [];
  }

}
