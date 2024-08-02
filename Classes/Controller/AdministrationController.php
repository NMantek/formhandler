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
  ) {}

  public function detailAction(Log $log): ResponseInterface {
    $startingPage = isset($this->request->getQueryParams()['logPage']) ? intval($this->request->getQueryParams()['logPage']) : 1;
    $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

    $moduleTemplate->assignMultiple([
      'log' => $log,
      'logPage' => $startingPage,
      'submittedValues' => $this->prepareFlatArray(unserialize($log->getParams())),
    ]);

    return $moduleTemplate->renderResponse('Administration/Detail');
  }

  public function indexAction(): ResponseInterface {
    $this->logEntries = $this->logRepository->getAllEntries();

    $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
    $startingPage = isset($this->request->getQueryParams()['logPage']) ? intval($this->request->getQueryParams()['logPage']) : 1;
    $paginator = new QueryResultPaginator($this->logEntries, $startingPage, 2);
    $pagination = new NumberedPagination($paginator);

    $moduleTemplate->assignMultiple([
      'pagination' => $pagination,
      'paginator' => $paginator,
      'logPage' => $startingPage,
    ]);

    return $moduleTemplate->renderResponse('Administration/Index');
  }

  public function selectFieldsAction(?string $logDataUids = null, string $filetype = ''): ResponseInterface {
    $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

    $logEntries = $this->logRepository->findByUids(array_map('intval', explode(',', $logDataUids)));
    $fieldsToShow = [
      'Global fields' => [
        'ip',
        'form_name',
        'submission_date',
        'form_page_id',
      ],
      'Custom fields' => [],
      'System fields' => [
        'key_hash',
        'unique_hash',
      ],
    ];

    foreach ($logEntries as $logDataRow) {
      $params = $this->prepareFlatArray(unserialize($logDataRow->getParams()));
      $fields = array_keys($params);
      foreach ($fields as $idx => $rowField) {
        if (!in_array($rowField, $fieldsToShow['Custom fields'])) {
          $fieldsToShow['Custom fields'][] = $rowField;
        }
      }
    }

    $moduleTemplate->assignMultiple([
      'fieldsToShow' => $fieldsToShow,
    ]);

    return $moduleTemplate->renderResponse('Administration/SelectFields');
  }

  /**
   * @param array<mixed> $formValues
   *
   * @return array<string, string>
   */
  protected function prepareFlatArray(array $formValues, string $passedName = ''): array {
    $returnArray = [];
    foreach ($formValues as $formValueKey => $formValueEntry) {
      if (is_array($formValueEntry)) {
        $returnArray = array_merge($this->prepareFlatArray($formValueEntry, $passedName.strval($formValueKey).'.'), $returnArray);
      } else {
        $returnArray[$passedName.$formValueKey] = strval($formValueEntry);
      }
    }

    return $returnArray;
  }
}
