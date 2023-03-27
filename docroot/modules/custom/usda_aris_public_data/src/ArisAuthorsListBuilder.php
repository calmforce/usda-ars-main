<?php

namespace Drupal\usda_aris_public_data;

use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Drupal\usda_ars_migrate\ArisDbQueryService;

/**
 * Helper class for ArisAuthorsListBlock.
 */
class ArisAuthorsListBuilder {

  /**
   * The default logger service.
   *
   * @var LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The ArisDbQueryService object.
   *
   * @var ArisDbQueryService
   */
  protected ArisDbQueryService $arisDbQueryService;

  /**
   * Constructs a new SorFieldManager.
   *
   * @param ArisDbQueryService $db_query_service
   *   ArisDbQueryService.
   * @param LoggerInterface $logger
   *   Default Logger.
   *
   */
  public function __construct(ArisDbQueryService $db_query_service, LoggerInterface $logger) {
    $this->arisDbQueryService = $db_query_service;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function buildAuthorsList($pub_id): array {

    $build = [];
    if (!empty($pub_id)) {
      $authors = $this->arisDbQueryService->getAuthorsForPublication($pub_id);
      if (!empty($authors)) {
        $build['wrapper'] = [
          '#type' => 'container',
          '#prefix' => '<div id="publication-authors-container" class="grid-container">',
          '#suffix' => '</div>',
          '#attributes' => [
            'class' => [
              'publication-authors-container',
              'container',
            ],
          ],
        ];
        $build['wrapper']['authors_list'] = [
          '#type' => 'html_tag',
          '#tag' => 'ul',
          '#attributes' => [
            'class' => 'pub_authors_list',
            'id' => 'pub_authors_list',
          ],
        ];
        foreach ($authors as $index => $author) {
          $build['wrapper']['authors_list'][$index]['li'] = [
            '#type' => 'html_tag',
            '#tag' => 'li',
            '#options' => ['attributes' => ['class' => 'pub-author-li']],
          ];
          if (!empty($author->PersonID)) {
            if (!empty($author->PersonCommonName)) {
              $name_line = $author->PersonLastName . ', ' . $author->PersonFirstName . ' - ' . $author->PersonCommonName;
            }
            else {
              $name_line = $author->PersonLastName . ', ' . $author->PersonFirstName;
            }
            $build['wrapper']['authors_list'][$index]['li']['link'] = [
              '#type' => 'link',
              '#url' => Url::fromUserInput('/people-locations/person/' . $author->PersonID),
              '#title' => $name_line,
              '#options' => ['attributes' => ['class' => 'pub-author-link', 'target' => '_blank']],
            ];
          }
          else {
            if (empty($author->EMP_ID)) {
              $employer = !empty($author->EmployerName) ? $author->EmployerName : $author->EMPLOYER;
              $name_line = !empty($employer) ? $author->PersonLastName . ', ' . $author->PersonFirstName . ' - ' . $employer :
                $author->PersonLastName . ', ' . $author->PersonFirstName;
            }
            else {
              $name_line = $author->PersonLastName . ', ' . $author->PersonFirstName;
            }
            $build['wrapper']['authors_list'][$index]['li']['#value'] = $name_line;
          }
        }
      }
    }

    return $build;
  }

}
