<?php

namespace Drupal\store_locator_import\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\store_locator_import\Form\WSSettingsForm;
use Drupal\store_locator_import\Service\ApCrmService;
use Drupal\user\Plugin\views\argument_default\CurrentUser;
use Drupal\Core\Database\Query\Select;

/**
 * Class XmlHandler.
 */
class XmlHandler
{

  /**
   * Drupal\Core\Logger\LoggerChannelInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;
  /**
   * Drupal\store_locator_import\Service\ApCrmService definition.
   *
   * @var \Drupal\store_locator_import\Service\ApCrmService
   */
  protected $apCrmService;
  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $connect;
  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var AccountProxy
   */
  protected $account;


  const TABLE = 'sap_download_log';

  /**
   * Constructs a new XmlHandler object.
   */
  public function __construct(LoggerChannelInterface $logger, ApCrmService $apCrmService, Connection $connection, AccountInterface $account)
  {
    $this->logger = $logger;
    $this->apCrmService = $apCrmService;
    $this->connection = $connection;
    $this->account = $account;
  }

  public function downloadAll(array $query = [])
  {
    if ($this->downloadNewXml()) {
      ini_set('max_execution_time', 300);
      $this->downloadTaxonomy($query);
      $this->downloadBrands($query);
      $this->downloadStores($query);
    } else {
      /* for debugging purposes, don't download new file from sap and use the previous file downloaded */
      $this->logger->debug('DEBUG:: Downloading new file from SAP is disabled.');
    }
  }

  public function downloadStores(array $query = [])
  {
    return $this->downloadXml(ApCrmService::RETAILER, $query, 1);
  }

  public function downloadTaxonomy(array $query = [])
  {
    return $this->downloadXml(ApCrmService::TAXONOMY, $query);
  }

  public function downloadBrands(array $query = [])
  {
    return $this->downloadXml(ApCrmService::BRANDS, $query);
  }

  protected function downloadXml(string $type, array $query = [], $archive = null)
  {
    if ($source = $this->apCrmService->getSource($type, $query)) {
      $filepath = file_create_filename('CRM-' . $type, 'public://') . '.xml';
      file_unmanaged_delete($filepath);

      if ($archive) {
        $this->archiveXml($type, $source);
      }

      if (file_unmanaged_save_data($this->convertEncoding($source), $filepath)) {
        return true;
      }
    }
    $error = 'Failed to get ' . $type . ' from SAP';
    $this->logger->error($error);
    \Drupal::messenger()->addError($error);
    return false;
  }

  /**
   * @param $string
   * @return string
   */
  protected function convertEncoding($string)
  {

    $pattern = '/(^<\?xml\ *version="[\d+\.]*"\ encoding=")(windows-1252)("\?>)/i';
    $string = preg_replace($pattern, "$1utf-8$3", $string, 1, $count);
    if ($count > 0) {
      $string = mb_convert_encoding($string, 'Windows-1252', "UTF-8");
    }
    return $string;
  }

  protected function archiveXml(string $type, $source)
  {
    $archivefilepath = file_create_filename('CRM-' . $type . '-archive-' . date('YmdHis'), 'public://XML/') . '.xml';
    file_unmanaged_save_data($source, $archivefilepath);
  }

  protected function downloadNewXml()
  {
    $config = \Drupal::configFactory()->get(WSSettingsForm::CONFIG_NAME)->get('wsinfo');
    if (isset($config['download_file']) && $config['download_file']) {
      return true;
    }
    return false;
  }

}
