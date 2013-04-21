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

use BedRest\Framework\Zend2\ServiceLocator;
use BedRest\Rest\RestManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * RestManagerFactory
 *
 * @author Geoff Adams <geoff@dianode.net>
 */
class RestManagerFactory implements FactoryInterface
{
    /**
     * Creates a RestManager.
     *
     * @param  \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \BedRest\Rest\RestManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $rm = new RestManager();
        
        $rm->setContentNegotiator($serviceLocator->get('bedrest.contentnegotiator'));
        $rm->setResourceMetadataFactory($serviceLocator->get('bedrest.resourcemetadatafactory'));
        $rm->setServiceMetadataFactory($serviceLocator->get('bedrest.servicemetadatafactory'));
        $rm->setServiceLocator(new ServiceLocator($serviceLocator));

        return $rm;
    }
}
