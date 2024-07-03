<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "Formhandler" by JAKOTA.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace Typoheads\Formhandler\Finisher;

use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use Typoheads\Formhandler\Definitions\Severity;
use Typoheads\Formhandler\Domain\Model\Config\Finisher\AbstractFinisherModel;
use Typoheads\Formhandler\Domain\Model\Config\Finisher\MailFinisherModel;
use Typoheads\Formhandler\Domain\Model\Config\FormModel;
use Typoheads\Formhandler\Domain\Model\Config\GeneralOptions\MailModel;
use Typoheads\Formhandler\Utility\Utility;

class MailFinisher extends AbstractFinisher {
  private FluidEmail $emailObject;

  private MailFinisherModel $finisherConfig;

  private FormModel $formConfig;

  private RequestInterface $request;

  private Utility $utility;

  public function process(FormModel &$formConfig, AbstractFinisherModel &$finisherConfig, RequestInterface $request): void {
    if (!$finisherConfig instanceof MailFinisherModel) {
      return;
    }
    $this->utility = GeneralUtility::makeInstance(Utility::class);

    $this->formConfig = $formConfig;
    $this->finisherConfig = $finisherConfig;
    $this->request = $request;

    // send usermail
    $this->sendMail($this->finisherConfig->userMailConfig, $this->formConfig->user, 'user');
    $this->sendMail($this->finisherConfig->adminMailConfig, $this->formConfig->admin, 'admin');
  }

  /**
   * Adds attachments to the current email object.
   * The specifield attachment names can be both direct filepaths and fieldnames for uploaded files.
   *
   * @param array<string, array{fileOrField: string, mime: null|string, renameTo: null|string}> $attachConfig
   */
  protected function addAttachments(array $attachConfig) {
    $rootPath = Environment::getPublicPath();

    $fileList = [];
    foreach ($attachConfig as $identifier => $attachFile) {
      // check if the given file was uploaded
      $files = $this->getFileFromUploads($attachFile['fileOrField']);
      if ($files) {
        $counter = 0;
        foreach ($files as $file) {
          $fileList[] = [
            'path' => $file->temp,
            'name' => ($attachFile['renameTo']) ? $identifier.'_'.$attachFile['renameTo'].$counter++ : $identifier.'_'.$file->name,
            'mime' => $file->type,
          ];
        }
      } else { // wasnt uploaded. Fallback to static file
        $fileList[] = [
          'path' => $this->utility->sanitizePath($rootPath.'/'.$attachFile['fileOrField']),
          'name' => $identifier,
          'mime' => $attachFile['mime'],
        ];
      }
    }

    foreach ($fileList as $file) {
      if (file_exists($file['path'])) {
        $this->emailObject->attachFromPath($file['path'], $file['name'], $file['mime']);
      } else {
        $this->formConfig->debugMessage('file_not_found', ["Embed file {$file['name']} with path {$file['path']} not found"], Severity::Info);
      }
    }
  }

  /**
   * Add embeds to the current email object.
   *
   * @param array<string, array{fileOrField: string, mime: null|string, renameTo: null|string}> $embedConfig
   */
  protected function addEmbeds(array $embedConfig): void {
    $rootPath = Environment::getPublicPath();

    foreach ($embedConfig as $identifier => $embedFile) {
      $file = $embedFile['fileOrField'];
      $fileMime = $embedFile['mime'];
      $filePath = $this->utility->sanitizePath($rootPath.'/'.$file);

      if (file_exists($filePath)) {
        $this->emailObject->embedFromPath($filePath, $identifier, $fileMime);
      } else {
        $this->formConfig->debugMessage('file_not_found', ["Embed file {$file} with path {$filePath} not found"], Severity::Info);
      }
    }
  }

  /**
   * Returns either the first (if both arent empty), the only non-empty parameter or an empty string.
   * Used when only one of two values (or neither) should be used.
   */
  protected function getAndTrimValueToSet(string $value1, string $value2): string {
    if (strlen($value1) > 0) {
      return trim($value1);
    }

    return trim($value2);
  }

  /**
   * Returns a valid email address of a given field.
   *
   * @return string the email adress. Will be empty if none was found
   */
  protected function getEmailAdressFromForm(string $fieldPath): string {
    $fieldPath = array_map('trim', explode('.', $fieldPath));

    $fieldValue = $this->utility->getFieldValue($fieldPath, $this->formConfig->formValues);
    if (!is_array($fieldValue) && GeneralUtility::validEmail(trim($fieldValue))) {
      return trim($fieldValue);
    }

    return '';
  }

  /**
   * @return false|FormUploadFile[]
   */
  protected function getFileFromUploads(string $fieldPath): array|false {
    $fieldPath = array_map('trim', explode('.', $fieldPath));
    foreach ($fieldPath as &$fieldPathLink) {
      $fieldPathLink = '['.$fieldPathLink.']';
    }
    $fieldPath = implode('', $fieldPath);

    if (isset($this->formConfig->formUploads->files[$fieldPath]) && is_array($this->formConfig->formUploads->files[$fieldPath])) {
      return $this->formConfig->formUploads->files[$fieldPath];
    }

    return false;
  }

  /**
   * Create a array of Addresses given a comma seperated string of emails and names.
   *
   * @return Address[]
   */
  protected function getListOfAdresses(string $configMails, string $configNames): array {
    $addresses = [];

    $finisherConfigNamesArray = explode(',', $configNames);
    foreach (explode(',', $configMails) as $emailToAdd) {
      if (strlen(trim($emailToAdd)) < 1) {
        continue;
      }

      $emailNameToAdd = array_shift($finisherConfigNamesArray);
      $addresses[] = new Address(trim($emailToAdd), trim($emailNameToAdd ?? ''));
    }

    return $addresses;
  }

  /**
   * Get a list of recipient email adresses.
   *
   * @param string $recipientAddresses a comma seperated list of fieldnames and/or email adresses
   *
   * @return Address[]
   */
  protected function getRecipientEmailAdresses(string $recipientAddresses): array {
    $recipientAddressArray = [];

    $recipientAddresses = array_unique(explode(',', $recipientAddresses));
    foreach ($recipientAddresses as $emailOrFieldName) {
      if (GeneralUtility::validEmail($emailOrFieldName)) {
        $recipientAddressArray[] = new Address($emailOrFieldName);
      } else {
        $emailFromField = $this->getEmailAdressFromForm($emailOrFieldName);

        if (strlen($emailFromField) > 0) {
          $recipientAddressArray[] = new Address($emailFromField);
        }
      }
    }

    return $recipientAddressArray;
  }

  /**
   * @param array{toEmail: string, subject: string, senderEmail: string, replyToEmail: string, replyToName: string, ccEmail: string, ccName: string, bccEmail: string, bccName: string, returnPath: string, templateMailHtml: string, templateMailText: string, attachments: array<string, array{fileOrField: string, mime: null|string, renameTo: null|string}>, embedFiles: array<string, array{fileOrField: string, mime: null|string, renameTo: null|string}>} $finisherConfig
   */
  protected function sendMail(array $finisherConfig, MailModel $formConfig, string $mailType): void {
    try {
      $path = new TemplatePaths();
      $fluidRootPaths = $this->utility->getFluidFilePaths();
      $path->setTemplateRootPaths($fluidRootPaths['templateRootPaths']);
      $path->setLayoutRootPaths($fluidRootPaths['layoutRootPaths']);
      $path->setPartialRootPaths($fluidRootPaths['partialRootPaths']);
      $this->emailObject = GeneralUtility::makeInstance(FluidEmail::class, $path);

      // set mail format
      $this->emailObject->format('html');
      // Get Controller & Action from request
      $this->emailObject->setRequest($this->request);
      // Default Template
      // TODO: Add Ability to change/override this Template. Could also be a static file path
      $this->emailObject->setTemplate('Finishers/MailFinisher');
      $this->emailObject->assign('mailForm', $this->getAndTrimValueToSet($finisherConfig['templateMailHtml'], $this->formConfig->templateMailHtml));
      // differentiate user and admin mail by type.
      $this->emailObject->assign('emailType', $mailType);

      // subject
      $this->emailObject->subject($this->getAndTrimValueToSet($finisherConfig['subject'], $formConfig->subject));

      // sender address
      $senderAddress = new Address($this->getAndTrimValueToSet($finisherConfig['senderEmail'], $formConfig->senderEmail), $this->getAndTrimValueToSet($finisherConfig['senderName'], $formConfig->senderName));
      $this->emailObject->sender($senderAddress);

      // replyTo addresses
      $replyToAddresses = array_merge($this->getListOfAdresses($finisherConfig['replyToEmail'], $finisherConfig['replyToName']), $this->getListOfAdresses($formConfig->replyToEmail, $formConfig->replyToName));
      $this->emailObject->replyTo(...$replyToAddresses);

      // cc addresses
      $ccAddressess = array_merge($this->getListOfAdresses($finisherConfig['ccEmail'], $finisherConfig['ccName']), $this->getListOfAdresses($formConfig->ccEmail, $formConfig->ccName));
      $this->emailObject->cc(...$ccAddressess);

      // bcc addresses
      $bccAddresses = array_merge($this->getListOfAdresses($finisherConfig['bccEmail'], $finisherConfig['bccName']), $this->getListOfAdresses($formConfig->bccEmail, $formConfig->bccName));
      $this->emailObject->bcc(...$bccAddresses);

      // return path (Mail to recieve non-delivery messages)
      $returnPath = trim($finisherConfig['returnPath']);
      if (0 === strlen($returnPath)) {
        $returnPath = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
      }
      if (strlen(trim($returnPath)) > 0) {
        $this->emailObject->returnPath($returnPath);
      }

      // embeds
      $this->addEmbeds($finisherConfig['embedFiles']);

      // attachments
      $this->addAttachments($finisherConfig['attachments']);

      // recipients
      $toEmailAdresses = $this->getRecipientEmailAdresses($finisherConfig['toEmail'].','.$formConfig->toEmail);
      $this->emailObject->to(...$toEmailAdresses);

      // variable assignment
      $this->emailObject->assignMultiple([
        ...$this->formConfig->formValues,
        'formValuePrefix' => $this->formConfig->formValuesPrefix,
      ]);

      $mailer = GeneralUtility::makeInstance(Mailer::class);
      $mailer->send($this->emailObject);
    } catch (\Exception $e) {
      // write to typo3 log
      $logger = GeneralUtility::makeInstance(LoggerInterface::class);
      $logger->error($e->getMessage());

      $this->formConfig->debugMessage('error', [$e->getMessage()], Severity::Error);
    }
  }
}
