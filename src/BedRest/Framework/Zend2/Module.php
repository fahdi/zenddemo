<?php

namespace BedRest\Framework\Zend2;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\Mvc\MvcEvent;

class Module implements
    AutoloaderProviderInterface,
    ConfigProviderInterface,
    InitProviderInterface
{
    protected $moduleDir;

    /**
     * {@inheritDoc}
     */
    public function init(ModuleManagerInterface $manager)
    {
        $this->moduleDir = realpath(__DIR__ . '/../../../../');
    }

    /**
     * {@inheritDoc}
     */
    public function onBootstrap(MvcEvent $e)
    {
        
    }
    
    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return include $this->moduleDir . '/config/module.config.php';
    }

    /**
     * {@inheritDoc}
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }
}
