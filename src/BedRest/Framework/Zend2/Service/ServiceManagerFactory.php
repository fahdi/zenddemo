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

namespace BedRest\Framework\Zend2\Service;

use BedRest\Service\ServiceManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * ServiceManagerFactory
 *
 * @author Geoff Adams <geoff@dianode.net>
 */
class ServiceManagerFactory implements FactoryInterface
{
    /**
     * Creates a ServiceManager
     * 
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \BedRest\Service\ServiceManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $serviceManager = new ServiceManager();
     
        $factory = $serviceLocator->get('bedrest.servicemetadatafactory');
        $serviceManager->setServiceMetadataFactory($factory);

        // service container
        $container = new ContainerBuilder();

        if ($serviceLocator->has('doctrine.entitymanager.orm_default')) {
            $container->setParameter(
                'doctrine.entityManager',
                $serviceLocator->get('doctrine.entitymanager.orm_default')
            );
        }
        
        $serviceManager->setServiceContainer($container);
        
        return $serviceManager;
    }
}
