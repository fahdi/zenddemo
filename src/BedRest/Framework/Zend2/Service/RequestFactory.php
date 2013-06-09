<?php

namespace BedRest\Framework\Zend2\Service;

use BedRest\Rest\Request\Request as RestRequest;
use BedRest\Rest\Request\Type as RestRequestType;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * RequestFactory
 *
 * @author Geoff Adams <geoff@dianode.net>
 */
class RequestFactory implements FactoryInterface
{
    /**
     * Creates a REST Request object.
     *
     * @param  \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \BedRest\Rest\Request\Request
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var \Zend\Mvc\Application $application */
        $application = $serviceLocator->get('Application');
        $routeMatch = $application->getMvcEvent()->getRouteMatch();
        $httpRequest = $application->getMvcEvent()->getRequest();

        $restRequest = new RestRequest();

        $id = $routeMatch->getParam('id', null);
        $subResourceName = $routeMatch->getParam('subresource', null);

        if (!empty($id) && !empty($subResourceName)) {
            $this->prepareSubResource($restRequest, $httpRequest, $routeMatch);
        } else {
            $this->prepareResource($restRequest, $httpRequest, $routeMatch);
        }

        $this->negotiateContent($restRequest, $httpRequest, $serviceLocator);

        return $restRequest;
    }

    /**
     * Prepares the REST Request object with values appropriate for a resource request.
     *
     * @param \BedRest\Rest\Request\Request $restRequest
     * @param \Zend\Http\Request            $httpRequest
     * @param \Zend\Mvc\Router\RouteMatch   $routeMatch
     */
    protected function prepareResource(RestRequest $restRequest, HttpRequest $httpRequest, RouteMatch $routeMatch)
    {
        $id = $routeMatch->getParam('id', null);
        if (!empty($id)) {
            $restRequest->setParameter('identifier', $id);
        }

        $restRequest->setResource($routeMatch->getParam('__CONTROLLER__'));

        $method = strtoupper($httpRequest->getMethod());
        if (!empty($method)) {
            if (empty($id) && $method !== RestRequestType::METHOD_POST) {
                $method .= '_COLLECTION';
            }

            $restRequest->setMethod(constant('BedRest\Rest\Request\Type::METHOD_' . $method));
        }
    }

    /**
     * Prepares the REST Request object with values appropriate for a sub-resource request.
     *
     * @param \BedRest\Rest\Request\Request $restRequest
     * @param \Zend\Http\Request            $httpRequest
     * @param \Zend\Mvc\Router\RouteMatch   $routeMatch
     */
    protected function prepareSubResource(RestRequest $restRequest, HttpRequest $httpRequest, RouteMatch $routeMatch)
    {
        $id = $routeMatch->getParam('id', null);
        $restRequest->setParameter('identifier', $id);

        $resourceName = $routeMatch->getParam('__CONTROLLER__');
        $subResourceName = $routeMatch->getParam('subresource', null);
        $restRequest->setResource($resourceName . '/' . $subResourceName);

        $subId = $routeMatch->getParam('subresource_id', null);
        if (!empty($subId)) {
            $restRequest->setParameter('subresource_identifier', $subId);
        }

        $method = strtoupper($httpRequest->getMethod());
        if (!empty($method)) {
            if (empty($subId) && $method !== RestRequestType::METHOD_POST) {
                $method .= '_COLLECTION';
            }

            $restRequest->setMethod(constant('BedRest\Rest\Request\Type::METHOD_' . $method));
        }
    }

    /**
     * Negotiates the request payload.
     *
     * @param \BedRest\Rest\Request\Request                $restRequest
     * @param \Zend\Http\Request                           $httpRequest
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     */
    protected function negotiateContent(
        RestRequest $restRequest,
        HttpRequest $httpRequest,
        ServiceLocatorInterface $serviceLocator
    ) {
        $content = $httpRequest->getContent();
        $contentType = $httpRequest->getHeader('Content-Type');

        if (!empty($content) && !empty($contentType)) {
            /** @var \BedRest\Content\Negotiation\Negotiator $negotiator */
            $negotiator = $serviceLocator->get('BedRest.ContentNegotiator');

            $restRequest->setContent($negotiator->decode($content, $contentType));
            $restRequest->setContentType($contentType);
        }
    }
}
