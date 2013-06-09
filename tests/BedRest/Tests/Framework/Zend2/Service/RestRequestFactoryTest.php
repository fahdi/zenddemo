<?php

namespace BedRest\Tests\Framework\Zend2\Service;

use BedRest\Framework\Zend2\Service\RestRequestFactory;
use BedRest\Rest\Request\Type as RestRequestType;
use Zend\Http\Request;

/**
 * RestRequestFactoryTest
 *
 * @author Geoff Adams <geoff@dianode.net>
 */
class RestRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \BedRest\Framework\Zend2\Service\RestRequestFactory */
    protected $factory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockRequest;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockRouteMatch;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockNegotiator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockServiceLocator;

    protected function setUp()
    {
        $this->factory = new RestRequestFactory();

        $this->mockRequest = $this->getMockBuilder('Zend\Http\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockRouteMatch = $this->getMockBuilder('Zend\Mvc\Router\Http\RouteMatch')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockNegotiator = $this->getMock('BedRest\Content\Negotiation\Negotiator');

        $this->mockServiceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $this->mockServiceLocator->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(array($this, 'serviceLocatorGet')));
    }

    public function serviceLocatorGet($name)
    {
        switch ($name) {
            case 'Request':
                return $this->mockRequest;
            case 'RouteMatch':
                return $this->mockRouteMatch;
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
        $this->mockRequest->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue($baseMethod));

        $this->mockRouteMatch->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->will($this->returnValue($id));

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
        $this->mockRouteMatch->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->will($this->returnValue($identifier));

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
}
