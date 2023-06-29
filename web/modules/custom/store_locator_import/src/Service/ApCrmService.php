<?php
/**
 * Created by PhpStorm.
 * User: cgrimoldi
 * Date: 13/07/18
 * Time: 17.49
 */

namespace Drupal\store_locator_import\Service;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\store_locator_import\Form\WSSettingsForm;
use GuzzleHttp\ClientInterface;

class ApCrmService {

  const LOGIN = 'login';
  const LOGOUT = 'logout';
  const AGENT = 'agent';
  const RETAILER = 'retailer';
  const TAXONOMY = 'taxonomy';
  const BRANDS = 'brands';

  private $client;
  private $host;
  private $basepath;
  private $username;
  private $password;

  public function __construct(ConfigFactoryInterface $config, ClientInterface $client) {
    $this->client = $client;
    $thisConf = $config->get(WSSettingsForm::CONFIG_NAME)->get('wsinfo');
    $this->host = $thisConf['host'];
    $this->basepath = $thisConf['basepath'];
    $this->username = $thisConf['username'];
    $this->password = $thisConf['password'];
  }

    /**
     * @param string $source
     * The call source
     *
     * @param array $query
     * Parameters for the call
     *
     * @return \Psr\Http\Message\StreamInterface
     */
  public function getSource(string $source, array $query) {
    $token = $this->login();
    $query['token'] = $token;
    $endpoint = $this->getEndpoint($source);
    try {
      /** @var \Psr\Http\Message\ResponseInterface $result */
      $result = $this->client->get($endpoint, ['query' => $query, 'timeout' => 3000]);
    }
    catch (\Exception $e) {
      $this->logout($token);
      throw new \RuntimeException('Error retrieving results', 0, $e);
    }
    $this->logout($token);
    if ($result->getStatusCode() != 200) {
      throw new \RuntimeException('Error retrieving results');
    }

    return $result->getBody();
  }

  /**
   * @return string
   * @throws \RuntimeException
   */
  public function login() {
    $endpoint = $this->getEndpoint(self::LOGIN);
    $query = [
      'username' => $this->username,
      'password' => $this->password,
    ];
    try {
      $result = $this->client->get($endpoint, ['query' => $query]);
    }
    catch (\Exception $e) {
      throw new \RuntimeException(sprintf('Error on login call [Code: %d ] [Exception: %s ]', $e->getCode(), $e->getMessage()), 0, $e);
    }
    if ($result->getStatusCode() != 200) {
      throw new \RuntimeException('Error on login response');
    }

    return trim($result->getBody()->getContents());
  }

  public function logout($token) {
    $endpoint = $this->getEndpoint(self::LOGOUT);
    $query = ['token' => $token,];
    try {
      $this->client->get($endpoint, ['query' => $query]);
    }
    catch (\Exception $e) {}
  }

  public function getEndpoint($type) {
    $url = $this->host . $this->basepath;
    switch ($type) {
      case self::LOGIN: return $url . '/login.do';
      case self::LOGOUT: return $url . '/logout';
      case self::AGENT: return $url . '/salesman';
//      case self::RETAILER: return $url . '/customernew';
      case self::RETAILER: return $url . '/CRMclienti';
      case self::BRANDS: return $url . '/CRMbrands';
      case self::TAXONOMY: return $url . '/CRMtassonomia';
    }

    throw new \RuntimeException(sprintf('Endpoint of type %s does not exixt', $type));
  }
}
