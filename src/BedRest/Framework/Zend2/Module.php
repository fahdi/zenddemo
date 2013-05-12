<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace BedRest\Framework\Zend2;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\Mvc\MvcEvent;

/**
 * Module.
 *
 * @author Geoff Adams <geoff@dianode.net>
 */
class Module implements
    AutoloaderProviderInterface,
    ConfigProviderInterface,
    InitProviderInterface,
    ServiceProviderInterface
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
        $app = $e->getApplication();
        $events = $app->getEventManager();

        // disable the default MVC strategies
        /** @var \Zend\Mvc\View\Http\ViewManager $viewManager */
        $viewManager = $app->getServiceManager()->get('ViewManager');
        /** @var \Zend\EventManager\EventManager $eventManager */
        $events->detach($viewManager->getRouteNotFoundStrategy());
        $events->detach($viewManager->getExceptionStrategy());
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
    public function getServiceConfig()
    {
        return include $this->moduleDir . '/config/services.config.php';
    }
}
