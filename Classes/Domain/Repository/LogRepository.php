<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "Formhandler" by JAKOTA.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace Typoheads\Formhandler\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Typoheads\Formhandler\Domain\Model\Log;

/**
 * The repository for Log.
 *
 * @extends \TYPO3\CMS\Extbase\Persistence\Repository<Log>
 */
class LogRepository extends Repository {
  /**
   * @param array<int> $uids
   *
   * @return QueryResultInterface<Log>
   */
  public function findByUids(array $uids) {
    $query = $this->createQuery();

    $query->getQuerySettings()->setRespectStoragePage(false);

    $query->matching(
      $query->logicalAnd(
        $query->in('uid', $uids),
      )
    );

    return $query->execute();
  }

  /**
   * @return QueryResultInterface<Log>
   */
  public function getAllEntries(?int $formPageId = null, ?string $formName = null, ?string $ip = null, ?string $startDate = null, ?string $endDate = null): QueryResultInterface {
    $query = $this->createQuery();
    $query->getQuerySettings()->setRespectStoragePage(false);

    $directConstraints = [
      'formPageId' => $formPageId,
      'formName' => $formName,
      'ip' => $ip,
    ];

    $matching = [];
    foreach ($directConstraints as $fieldName => $constraint) {
      if (!$constraint) {
        continue;
      }

      $matching[] = $query->equals($fieldName, $constraint);
    }

    if ($startDate) {
      $time = new \DateTime($startDate);
      $matching[] = $query->greaterThanOrEqual('crdate', $time->getTimestamp());
    }
    if ($endDate) {
      $time = new \DateTime($endDate);
      $matching[] = $query->lessThanOrEqual('crdate', $time->getTimestamp());
    }

    $query->matching(
      $query->logicalAnd(
        ...$matching
      )
    );

    return $query->execute();
  }

  /**
   * @return array<mixed>
   */
  public function getAllFormNames(): array {
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_formhandler_domain_model_log');

    $queryBuilder
      ->select('form_name')
      ->from('tx_formhandler_domain_model_log')
      ->groupBy('form_name')
    ;

    $results = $queryBuilder->executeQuery()->fetchAllAssociativeIndexed();

    return array_keys($results);
  }
}
