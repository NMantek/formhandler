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

use JAKOTA\Typo3ToolBox\Utility\DebuggerUtility;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

  private Utility $utility;

  public function process(FormModel &$formConfig, AbstractFinisherModel &$finisherConfig): void {
    if (!$finisherConfig instanceof MailFinisherModel) {
      return;
    }
    $this->utility = GeneralUtility::makeInstance(Utility::class);

    $this->formConfig = $formConfig;
    $this->finisherConfig = $finisherConfig;

    // send usermail
    $this->initMailer($this->finisherConfig->userMailConfig, $this->formConfig->user);

    exit;
  }

  /**
   * @param array<string, array{fileOrField: string, mime: null|string, renameTo: null|string}> $attachConfig
   */
  protected function addAttachments(array $attachConfig) {
    $rootPath = Environment::getPublicPath();

    foreach ($attachConfig as $identifier => $attachFile) {
      // check if the given file was uploaded
      $files = $this->getFileFromUploads($attachFile['fileOrField']);
      if ($files) {
        $counter = 0;
        foreach ($files as $file) {
          $files = [
            'path' => $file->temp,
            'name' => ($attachFile['renameTo']) ? $identifier.'_'.$attachFile['renameTo'].$counter++ : $identifier.'_'.$file->name,
            'mime' => $file->type,
          ];
        }
      } else { // wasnt uploaded. Fallback to static file
        $files = [
          'path' => $this->utility->sanitizePath($rootPath.'/'.$attachFile['fileOrField']),
          'name' => $identifier,
          'mime' => $attachFile['mime'],
        ];
      }

      foreach ($files as $file) {
        if (file_exists($file['path'])) {
          $this->emailObject->attachFromPath($file['path'], $file['name'], $file['mime']);
        } else {
          $this->formConfig->debugMessage('file_not_found', ["Embed file {$file['name']} with path {$file['path']} not found"], Severity::Info);
        }
      }
    }
  }

  /**
   * @param array<string, array{fileOrField: string, mime: null|string, renameTo: null|string}> $embedConfig
   */
  protected function addEmbeds(array $embedConfig) {
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

  protected function getAndTrimValueToSet(string $value1, string $value2): false|string {
    if (strlen($value1) > 0) {
      return trim($value1);
    }

    return trim($value2);
  }

  protected function getEmailAdressFromForm(string $fieldPath) {
    $fieldPath = array_map('trim', explode('.', $fieldPath));

    return $this->utility->getFieldValue($fieldPath, $this->formConfig->formValues);
  }

  protected function getFileFromUploads(string $fieldPath) {
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

  protected function getListOfAdresses(string $finisherConfigMails, string $finisherConfigNames) {
    $addresses = [];

    $finisherConfigNamesArray = explode(',', $finisherConfigNames);
    foreach (explode(',', $finisherConfigMails) as $finisherConfigMail) {
      if (strlen(trim($finisherConfigMail)) < 1) {
        continue;
      }

      $finisherConfigName = array_shift($finisherConfigNamesArray);
      $addresses[] = new Address(trim($finisherConfigMail), trim($finisherConfigName ?? ''));
    }

    return $addresses;
  }

  /**
   * @param array{toEmail: string, subject: string, senderEmail: string, replyToEmail: string, replyToName: string, ccEmail: string, ccName: string, bccEmail: string, bccName: string, returnPath: string, attachments: array<string, array{fileOrField: string, mime: null|string, renameTo: null|string}>, embedFiles: array<string, array{fileOrField: string, mime: null|string, renameTo: null|string}>} $finisherConfig
   */
  protected function initMailer(array $finisherConfig, MailModel $formConfig): void {
    try {
      $this->emailObject = GeneralUtility::makeInstance(FluidEmail::class);

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
    } catch (\Exception $e) {
      throw $e;
      // DebuggerUtility::var_dump($e);
      $this->formConfig->debugMessage('error', [$e->getMessage()], Severity::Error);
    }
  }
}
