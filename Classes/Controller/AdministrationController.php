<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "Formhandler" by JAKOTA.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace Typoheads\Formhandler\Controller;

use GeorgRinger\NumberedPagination\NumberedPagination;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Typoheads\Formhandler\Domain\Model\Log;
use Typoheads\Formhandler\Domain\Repository\LogRepository;

#[Controller]
final class AdministrationController extends ActionController {
  /** @var array|QueryResultInterface<Log> */
  protected array|QueryResultInterface $logEntries;

  public function __construct(
    protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    protected readonly IconFactory $iconFactory,
    protected readonly LogRepository $logRepository,
  ) {
    $this->logEntries = $this->logRepository->getAllEntries();
  }

  public function indexAction(): ResponseInterface {
    $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
    $startingPage = isset($this->request->getQueryParams()['logPage']) ? intval($this->request->getQueryParams()['logPage']) : 1;
    $paginator = new QueryResultPaginator($this->logEntries, $startingPage, 2);
    $pagination = new NumberedPagination($paginator);

    $moduleTemplate->assignMultiple([
      'pagination' => $pagination,
      'paginator' => $paginator,
    ]);

    return $moduleTemplate->renderResponse('Administration/Index');
  }
}
