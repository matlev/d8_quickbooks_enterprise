<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle;

use Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\SoapService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class SoapServiceController extends ControllerBase {

  /**
   * @var \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\SoapService
   */
  private $soapService;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $logger;

  /**
   * @var \SoapServer
   */
  protected $server;

  /**
   * SoapServiceController constructor.
   *
   * @param \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\SoapService $soapService
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   */
  public function __construct(SoapService $soapService, LoggerChannelFactoryInterface $logger) {
    // Instantiate the services.
    $this->soapService = $soapService;
    $this->logger = $logger;
  }

  /**
   * Inject the soap_service and logger services into this controller.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    /**
     * @var $soapService SoapService
     */
    $soapService = $container->get('commerce_quickbooks_enterprise.soap_service');

    /**
     * @var  $logger LoggerChannelFactoryInterface
     */
    $logger = $container->get('logger.factory');

    return new static($soapService, $logger);
  }

  /**
   * Construct the SOAP service and handle the request.
   *
   * @TODO: Pass in WSDL file location as a parameter.
   */
  public function handleRequest() {
    // Clear the wsdl caches.
    ini_set('soap.wsdl_cache_enabled',0);
    ini_set('soap.wsdl_cache_ttl',0);

    // Create the Soap server.
    $this->server = new \SoapServer(__DIR__ . '/QBWebConnectorSvc.wsdl');
    $this->server->setObject($this->soapService);

    $response = new Response();
    $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

    ob_start();
    $this->server->handle();
    $response->setContent(ob_get_clean());

    return $response;
  }

}