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

  public function getAllEntries() {
    $query = $this->createQuery();

    $query->getQuerySettings()->setRespectStoragePage(false);

    return $query->execute();
  }
}
