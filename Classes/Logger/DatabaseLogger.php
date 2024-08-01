<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "Formhandler" by JAKOTA.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace Typoheads\Formhandler\Logger;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Typoheads\Formhandler\Domain\Model\Config\FormModel;
use Typoheads\Formhandler\Domain\Model\Config\Logger\AbstractLoggerModel;
use Typoheads\Formhandler\Domain\Model\Config\Logger\DatabaseLoggerModel;
use Typoheads\Formhandler\Domain\Model\Log;
use Typoheads\Formhandler\Domain\Repository\LogRepository;

class DatabaseLogger extends AbstractLogger {
  protected Log $logEntry;

  protected LogRepository $logRepository;

  protected PersistenceManager $persistenceManager;

  protected Random $random;

  public function process(FormModel &$formConfig, AbstractLoggerModel &$loggerConfig): void {
    if (!$loggerConfig instanceof DatabaseLoggerModel) {
      return;
    }

    $this->random = GeneralUtility::makeInstance(Random::class);
    $this->logRepository = GeneralUtility::makeInstance(LogRepository::class);
    $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

    $this->logEntry = new Log();
    $this->logEntry->setIp(GeneralUtility::getIndpEnv('REMOTE_ADDR'));

    /** @var ServerRequestInterface $request */
    $request = $GLOBALS['TYPO3_REQUEST'];
    $this->logEntry->setFormPageId($request->getAttribute('routing')?->getPageId() ?? 0);

    $this->logEntry->setFormName($formConfig->formName);

    $this->logEntry->setParams(serialize($formConfig->formValues));

    $this->logEntry->setKeyHash(md5(serialize(array_keys($formConfig->formValues))));

    $this->logEntry->setUniqueHash(sha1(sha1($this->logEntry->getParams().$this->logEntry->getKeyHash().strval(time()))));

    // TODO: Replace with spam detection or remove field
    $this->logEntry->setIsSpam(false);

    // TODO: add ability to override in typoscript
    $this->logEntry->setPid(0);

    $this->logRepository->add($this->logEntry);
    $this->persistenceManager->persistAll();
  }
}
