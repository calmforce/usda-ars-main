<?php

namespace Drupal\usda_ars_migrate;

use Drupal;

/**
 * Provides methods to query the Umbraco MSSQL Migration DB.
 */
class UmbracoDbQueryService extends MssqlDbConnection {

  /**
   * Constructs the UmbracoDbQueryService.
   */
  public function __construct() {
    $this->connection = $this->getDatabase('migrate');
  }

  /**
   * Gets ARS Node properties for the given node id.
   *
   * @param int $node_id
   *   The node id.
   *
   * @return array|mixed
   *   NULL or empty array if failed, array of parameters is successful.
   */
  public function getNodeProperties($node_id) {
    try {
      $connection = $this->getConnection();

      $query_str = "SELECT
        v.VersionDate
        , pt.[Name] AS [Property Name]
        , pd.propertytypeid
        , pd.dataInt
        , pd.dataDate
        , pd.dataNvarchar
        , pd.dataNtext
        ,*
        FROM
        (
          SELECT
        cv.ContentId
        , cv.VersionId
        , cv.VersionDate
        , ROW_NUMBER() OVER (PARTITION BY cv.ContentId ORDER BY cv.VersionDate DESC) AS rn
        FROM
        cmsContentVersion cv
        WHERE
        cv.ContentId = :nid
        ) v
        LEFT JOIN umbracoNode n ON n.id = v.ContentId
        LEFT JOIN cmsPropertyData pd ON n.id = pd.contentNodeId AND v.VersionId = pd.VersionId
        LEFT JOIN cmsPropertyType pt ON pd.propertytypeid = pt.id
        WHERE
        v.rn = 1";

      $query = $connection->prepare($query_str);
      $query->execute(['nid' => $node_id]);
      $raw_data = $query->fetchAll();

    }
    catch (\Exception $e) {
      Drupal::logger('usda_ars_drupal')->error('Error connecting to MSSQL database. Error code: @code, @message.',
        array(
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ));
    }

    $data = [];
    foreach ($raw_data as $row) {
      $data[$row->Alias] = $row;
    }
    return $data;
  }


  /**
   * Gets ARS Scientific Discoveries Node properties for the given node id.
   *
   * @param int $node_id
   *   The node id.
   *
   * @return array|mixed
   *   NULL or empty array if failed, array of parameters is successful.
   */
  public function getSDNodeProperties($node_id) {
    try {
      $connection = $this->getConnection();

      $query_str = "SELECT
        v.VersionDate as updateDate
        , pt.[Name] AS [Property Name]
        , pt.[Alias] AS [Property Alias]
        , pd.propertytypeid
        , pd.intValue
        , pd.dateValue
        , pd.varcharValue
        , pd.textValue
        ,*
        FROM
        (
          SELECT
        cv.nodeId
        , cv.id
        , cv.VersionDate
        , ROW_NUMBER() OVER (PARTITION BY cv.nodeId ORDER BY cv.VersionDate DESC) AS rn
        FROM
        umbracoContentVersion cv
        WHERE
        cv.nodeId = :nid
        AND cv.[current] = 1
        ) v
        LEFT JOIN umbracoNode n ON n.id = v.nodeId
        LEFT JOIN umbracoContentVersion cv ON cv.nodeId = n.id
        LEFT JOIN umbracoPropertyData as pd on pd.versionId = cv.id
        LEFT JOIN cmsPropertyType pt ON pd.propertytypeid = pt.id
        WHERE
        v.rn = 1";

      $query = $connection->prepare($query_str);
      $query->execute(['nid' => $node_id]);
      $raw_data = $query->fetchAll();

    }
    catch (\Exception $e) {
      Drupal::logger('usda_ars_drupal')->error('Error connecting to MSSQL database. Error code: @code, @message.',
        array(
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ));
    }

    $data = [];
    foreach ($raw_data as $row) {
      if ($row->current) {
        $data[$row->Alias] = $row;
        if (empty($data['updateDate'])) {
          $data['updateDate'] = $row->updateDate;
        }
      }
    }
    return $data;
  }

  /**
   * Gets ARS properties for the given person id.
   *
   * @param int $person_id
   *   The person id.
   *
   * @return array|mixed
   *   NULL or empty array if failed, array of parameters is successful.
   */

public function getPersonSiteNodeIdForPersonId($person_id) {
    try {
      $connection = $this->getConnection();

      $query = $connection->select('cmsPropertyData', 'pd');
      $query->addField('pd', 'contentNodeId');
      $query->innerJoin('cmsPropertyType', 'pt', 'pd.propertytypeid = pt.id');
      $query->innerJoin('cmsDocument', 'Doc', 'pd.contentNodeId = Doc.nodeId AND pd.versionId = Doc.versionId');
      $query->condition('pt.Name', 'Person Link');
      $query->condition('pd.dataNvarchar', $person_id);
      $query->condition('Doc.published', '1');

      $raw_data = $query->execute()->fetchAll();

    } catch (\Exception $e) {
      Drupal::logger('usda_ars_drupal')->error('Error connecting to MSSQL database. Error code: @code, @message.',
              array(
                  '@code' => $e->getCode(),
                  '@message' => $e->getMessage(),
      ));
    }

    $data = '';
    foreach ($raw_data as $row) {
      $data = $row->contentNodeId;
    }
    return $data;
  }

  /**
   * Gets ARS Scientific Discoveries Node ID for the given UUID.
   *
   * @param string $uuid
   *   The UUID.
   *
   * @return int|mixed
   *   NULL if failed, Node ID is successful.
   */
  public function getNodeIdForUUID($uuid) {
    try {
      $connection = $this->getConnection();
      $query = $connection->select('umbracoNode', 'n');
      $query->addField('n', 'id');
      $query->condition('n.uniqueId', $uuid);

      $raw_data = $query->execute()->fetchAll();
    }
    catch (\Exception $e) {
      Drupal::logger('usda_ars_drupal')->error('Error connecting to MSSQL database. Error code: @code, @message.',
        array(
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ));
    }
    $nid = NULL;
    foreach ($raw_data as $row) {
      $nid = $row->id;
    }
    return $nid;
  }

}
