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

namespace BedRest\Framework\Zend2\Mvc\Controller;

use BedRest\Framework\Zend2\View\Model\ViewModel;
use BedRest\Rest\Request\Request;
use Zend\Mvc\Controller\AbstractController as ZendAbstractController;
use Zend\Mvc\Exception;
use Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\Stdlib\RequestInterface as ZendRequest;
use Zend\Stdlib\ResponseInterface as ZendResponse;

/**
 * AbstractRestfulController
 *
 * @author Geoff Adams <geoff@dianode.net>
 */
abstract class AbstractRestfulController extends ZendAbstractController
{
    /**
     * @var string
     */
    protected $resourceName = '';
    
    /**
     * @var \BedRest\Rest\Request\Request
     */
    protected $bedRestRequest;

    public function dispatch(ZendRequest $request, ZendResponse $response = null)
    {
        if (!$request instanceof HttpRequest) {
            throw new Exception\InvalidArgumentException('Expected an HTTP request');
        }
        
        $e = $this->getEvent();
        
        $this->bedRestRequest = $this->createBedRestRequest($request, $e->getRouteMatch());
        
        return parent::dispatch($request, $response);
    }
    
    public function onDispatch(MvcEvent $e)
    {
        $request = $this->bedRestRequest;

        /** @var \BedRest\Resource\Mapping\ResourceMetadataFactory $resourceMetadataFactory */
        $resourceMetadataFactory = $this->getServiceLocator()->get('BedRest.ResourceMetadataFactory');
        $resourceMetadata = $resourceMetadataFactory->getMetadataByResourceName($request->getResource());

        $service = $this->getServiceLocator()->get($resourceMetadata->getService());

        /** @var \BedRest\Service\Mapping\ServiceMetadataFactory $serviceMetadataFactory */
        $serviceMetadataFactory = $this->getServiceLocator()->get('BedRest.ServiceMetadataFactory');
        $serviceMetadata = $serviceMetadataFactory->getMetadataFor(get_class($service));

        $listeners = $serviceMetadata->getListeners($request->getMethod());

        $data = array();
        foreach ($listeners as $listener) {
            $data[] = $service->$listener($request);
        }

        if (count($data) === 1) {
            $data = reset($data);
        }
        
        // TODO: investigate whether there is another way to pass the Accept list through to the renderer
        $result = new ViewModel($data);
        $result->setAccept($request->getAccept());

        $e->setResult($result);

        return $result;
    }
    
    /**
     * @param  \Zend\Mvc\MvcEvent            $e
     * @return \BedRest\Rest\Request\Request
     */
    protected function createBedRestRequest(HttpRequest $request, RouteMatch $routeMatch)
    {
        $brRequest = new Request();

        $method = strtoupper($request->getMethod());
        $id = $routeMatch->getParam('id', false);
        
        if ($id === false) {
            $method .= '_COLLECTION';
        } else {
            $brRequest->setParameter('identifier', $id);
        }
        
        $brRequest->setMethod(constant('BedRest\Rest\Request\Type::METHOD_' . $method));
        // TODO: need to correlate controllers with the resources
        $brRequest->setResource($this->resourceName);
        $brRequest->setContent($request->getContent());

        return $brRequest;
    }
}
