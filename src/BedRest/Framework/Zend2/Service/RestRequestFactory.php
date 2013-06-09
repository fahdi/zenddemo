<?php

namespace BedRest\Framework\Zend2\Service;

use BedRest\Rest\Request\Request;
use BedRest\Rest\Request\Type as RestRequestType;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * RestRequestFactory
 *
 * @author Geoff Adams <geoff@dianode.net>
 */
class RestRequestFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $request = $serviceLocator->get('Request');
        $routeMatch = $serviceLocator->get('RouteMatch');
        $restRequest = new Request();

        $id = $routeMatch->getParam('id', false);
        if (!empty($id)) {
            $restRequest->setParameter('identifier', $id);
        }

        $method = strtoupper($request->getMethod());
        if (!empty($method)) {
            if (empty($id) && $method !== RestRequestType::METHOD_POST) {
                $method .= '_COLLECTION';
            }
            $restRequest->setMethod(constant('BedRest\Rest\Request\Type::METHOD_' . $method));
        }

        $content = $request->getContent();
        $contentType = $request->getHeader('Content-Type');

        if (!empty($content) && !empty($contentType)) {
            /** @var \BedRest\Content\Negotiation\Negotiator $negotiator */
            $negotiator = $serviceLocator->get('BedRest.ContentNegotiator');

            $restRequest->setContent($negotiator->decode($content, $contentType));
            $restRequest->setContentType($contentType);
        }

        return $restRequest;
    }
}
