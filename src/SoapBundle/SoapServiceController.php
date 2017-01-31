<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle;

use Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\SoapService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class SoapServiceController extends ControllerBase {

  /**
   * @var \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\SoapService
   */
  protected $soapService;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;


  protected $moduleHandler;

  /**
   * @var \SoapServer
   */
  protected $server;

  /**
   * SoapServiceController constructor.
   *
   * @param \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\SoapService $soapService
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(
    SoapService $soapService,
    LoggerChannelFactoryInterface $logger,
    ModuleHandlerInterface $moduleHandler
  ) {
    // Instantiate the services.
    $this->soapService = $soapService;
    $this->logger = $logger;
    $this->moduleHandler = $moduleHandler;
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
     * @var $logger LoggerChannelFactoryInterface
     */
    $logger = $container->get('logger.factory');

    /**
     * @var $moduleHandler ModuleHandlerInterface
     */
    $moduleHandler = $container->get('module_handler');

    return new static($soapService, $logger, $moduleHandler);
  }

  /**
   * Construct the SOAP service and handle the request.
   *
   * @TODO: Pass in WSDL file location as a parameter.
   */
  public function handleRequest() {
    // Allow other modules to make changes to the SOAP service, such as swapping
    // out the validation or qbxml parser plugins.
    $this->moduleHandler->alter('commerce_quickbooks_enterprise_soapservice', $this->soapService);

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