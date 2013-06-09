<?php

namespace BedRest\Tests\Framework\Zend2\Service;

use BedRest\Framework\Zend2\Service\RequestFactory;
use BedRest\Rest\Request\Type as RestRequestType;
use Zend\Http\Request;

/**
 * RequestFactoryTest
 *
 * @author Geoff Adams <geoff@dianode.net>
 */
class RequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \BedRest\Framework\Zend2\Service\RequestFactory */
    protected $factory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockApplication;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockEvent;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockRequest;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockRouteMatch;

    /** @var array */
    protected $routeMatchParams = array();

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockNegotiator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockServiceLocator;

    protected function setUp()
    {
        $this->factory = new RequestFactory();

        $this->mockRequest = $this->getMockBuilder('Zend\Http\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockRouteMatch = $this->getMockBuilder('Zend\Mvc\Router\Http\RouteMatch')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockRouteMatch->expects($this->any())
            ->method('getParam')
            ->with($this->isType('string'))
            ->will($this->returnCallback(array($this, 'routeMatchGetParam')));

        $this->mockEvent = $this->getMock('Zend\Mvc\MvcEvent');

        $this->mockEvent->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->mockRequest));

        $this->mockEvent->expects($this->any())
            ->method('getRouteMatch')
            ->will($this->returnValue($this->mockRouteMatch));

        $this->mockApplication = $this->getMockBuilder('Zend\Mvc\Application')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockApplication->expects($this->any())
            ->method('getMvcEvent')
            ->will($this->returnValue($this->mockEvent));

        $this->mockNegotiator = $this->getMock('BedRest\Content\Negotiation\Negotiator');

        $this->mockServiceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $this->mockServiceLocator->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(array($this, 'serviceLocatorGet')));
    }

    public function routeMatchGetParam($name)
    {
        if (isset($this->routeMatchParams[$name])) {
            return $this->routeMatchParams[$name];
        }

        return null;
    }

    public function serviceLocatorGet($name)
    {
        switch ($name) {
            case 'Application':
                return $this->mockApplication;
            case 'BedRest.ContentNegotiator':
                return $this->mockNegotiator;
        }

        return null;
    }

    /**
     * @test
     */
    public function restRequestCreated()
    {
        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
    }

    public function sampleRequests()
    {
        return array(
            array('GET', 123, RestRequestType::METHOD_GET),
            array('GET', null, RestRequestType::METHOD_GET_COLLECTION),
            array('POST', 123, RestRequestType::METHOD_POST),
            array('POST', null, RestRequestType::METHOD_POST),
            array('PUT', 123, RestRequestType::METHOD_PUT),
            array('PUT', null, RestRequestType::METHOD_PUT_COLLECTION),
            array('DELETE', 123, RestRequestType::METHOD_DELETE),
            array('DELETE', null, RestRequestType::METHOD_DELETE_COLLECTION),
        );
    }

    /**
     * @test
     * @dataProvider sampleRequests
     */
    public function methodIsCorrectlyDetermined($baseMethod, $id, $expectedMethod)
    {
        $this->routeMatchParams['id'] = $id;

        $this->mockRequest->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue($baseMethod));

        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals($expectedMethod, $restRequest->getMethod());
    }

    /**
     * @test
     */
    public function identifierSet()
    {
        $identifier = 123;
        $this->routeMatchParams['id'] = $identifier;

        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals($identifier, $restRequest->getParameter('identifier'));
    }

    /**
     * @test
     */
    public function identifierNotSetIfUnavailable()
    {
        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals(null, $restRequest->getParameter('identifier'));
    }

    /**
     * @test
     */
    public function requestContentIsNegotiatedIfPresent()
    {
        $requestData = 'some request data';
        $contentType = 'foo/bar';
        $convertedData = 'some converted data';

        $this->mockRequest->expects($this->atLeastOnce())
            ->method('getContent')
            ->will($this->returnValue($requestData));

        $this->mockRequest->expects($this->atLeastOnce())
            ->method('getHeader')
            ->with('Content-Type')
            ->will($this->returnValue($contentType));

        $this->mockNegotiator->expects($this->once())
            ->method('decode')
            ->with($requestData, $contentType)
            ->will($this->returnValue($convertedData));

        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals($convertedData, $restRequest->getContent());
    }

    /**
     * @test
     */
    public function requestContentIsEmptyIfContentTypeMissing()
    {
        $requestData = 'some request data';

        $this->mockRequest->expects($this->atLeastOnce())
            ->method('getContent')
            ->will($this->returnValue($requestData));

        $this->mockRequest->expects($this->atLeastOnce())
            ->method('getHeader')
            ->with('Content-Type')
            ->will($this->returnValue(null));

        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals(null, $restRequest->getContent());
    }

    /**
     * @test
     */
    public function requestContentIsEmptyIfContentMissing()
    {
        $contentType = 'foo/bar';

        $this->mockRequest->expects($this->atLeastOnce())
            ->method('getContent')
            ->will($this->returnValue(null));

        $this->mockRequest->expects($this->atLeastOnce())
            ->method('getHeader')
            ->with('Content-Type')
            ->will($this->returnValue($contentType));

        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals(null, $restRequest->getContent());
    }

    /**
     * @test
     */
    public function resourceNameIsDeterminedFromController()
    {
        $resourceName = 'test';
        $this->routeMatchParams['__CONTROLLER__'] = $resourceName;

        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals($resourceName, $restRequest->getResource());
    }

    /**
     * @test
     */
    public function subResourceNameIsCorrectlyDetermined()
    {
        $resourceName = 'test';
        $this->routeMatchParams['__CONTROLLER__'] = $resourceName;
        $subResourceName = 'sub';
        $this->routeMatchParams['subresource'] = $subResourceName;

        $fqResource = $resourceName . '/' . $subResourceName;

        // without a resource ID
        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals($resourceName, $restRequest->getResource());

        // with a resource ID
        $this->routeMatchParams['id'] = 123;

        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals($fqResource, $restRequest->getResource());
    }

    public function sampleSubResourceIdentifierRequests()
    {
        return array(
            array('test', 123, 'sub', 456, true),
            array('test', 123, 'sub', null, false),
            array('test', 123, null, 456, false),
            array('test', 123, null, null, false),
            array('test', null, 'sub', 456, false),
            array('test', null, 'sub', null, false),
            array('test', null, null, 456, false),
            array('test', null, null, null, false)
        );
    }

    /**
     * @test
     * @dataProvider sampleSubResourceIdentifierRequests
     */
    public function subResourceIdentifierSetCorrectly($resourceName, $id, $subResourceName, $subId, $shouldBeSet)
    {
        $this->routeMatchParams['__CONTROLLER__'] = $resourceName;
        $this->routeMatchParams['id'] = $id;
        $this->routeMatchParams['subresource'] = $subResourceName;
        $this->routeMatchParams['subresource_id'] = $subId;

        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);

        if ($shouldBeSet) {
            $this->assertEquals($subId, $restRequest->getParameter('subresource_identifier'));
        } else {
            $this->assertEmpty($restRequest->getParameter('subresource_identifier'));
        }
    }

    public function sampleSubResourceRequests()
    {
        return array(
            array('GET', 456, RestRequestType::METHOD_GET),
            array('GET', null, RestRequestType::METHOD_GET_COLLECTION),
            array('POST', 456, RestRequestType::METHOD_POST),
            array('POST', null, RestRequestType::METHOD_POST),
            array('PUT', 456, RestRequestType::METHOD_PUT),
            array('PUT', null, RestRequestType::METHOD_PUT_COLLECTION),
            array('DELETE', 456, RestRequestType::METHOD_DELETE),
            array('DELETE', null, RestRequestType::METHOD_DELETE_COLLECTION),
        );
    }

    /**
     * @test
     * @dataProvider sampleSubResourceRequests
     */
    public function methodIsCorrectlyDeterminedForSubResources($baseMethod, $subId, $expectedMethod)
    {
        $id = 123;
        $resourceName = 'test';
        $subResourceName = 'sub';

        $this->routeMatchParams['id'] = $id;
        $this->routeMatchParams['subresource_id'] = $subId;
        $this->routeMatchParams['__CONTROLLER__'] = $resourceName;
        $this->routeMatchParams['subresource'] = $subResourceName;

        $this->mockRequest->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue($baseMethod));

        $restRequest = $this->factory->createService($this->mockServiceLocator);
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals($expectedMethod, $restRequest->getMethod());
    }
}
