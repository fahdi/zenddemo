<?php

namespace BedRest\Tests\Framework\Zend2\Mvc\Controller;

use BedRest\Rest\Request\Type as RestRequestType;
use BedRest\TestFixtures\ConcreteController;
use Zend\Http\Request;

/**
 * AbstractRestControllerTest
 *
 * @author Geoff Adams <geoff@dianode.net>
 */
class AbstractRestControllerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \BedRest\TestFixtures\ConcreteController */
    protected $controller;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockRequest;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockEvent;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockRouteMatch;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockServiceLocator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockNegotiator;

    protected function setUp()
    {
        $this->mockRequest = $this->getMockBuilder('Zend\Http\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockRouteMatch = $this->getMockBuilder('Zend\Mvc\Router\Http\RouteMatch')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockEvent = $this->getMockBuilder('Zend\Mvc\MvcEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockEvent->expects($this->any())
            ->method('getRouteMatch')
            ->will($this->returnValue($this->mockRouteMatch));

        $this->mockEvent->expects($this->any())
            ->method('setRequest')
            ->will($this->returnSelf());

        $this->mockEvent->expects($this->any())
            ->method('setResponse')
            ->will($this->returnSelf());

        $this->mockEvent->expects($this->any())
            ->method('setTarget')
            ->will($this->returnSelf());

        $this->mockDispatcher = $this->getMock('BedRest\Rest\Dispatcher');

        $this->mockNegotiator = $this->getMock('BedRest\Content\Negotiation\Negotiator');

        $this->mockServiceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');

        $this->mockServiceLocator->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(array($this, 'serviceLocatorGet')));

        $this->controller = new ConcreteController();
        $this->controller->setEvent($this->mockEvent);
        $this->controller->setServiceLocator($this->mockServiceLocator);
    }

    public function serviceLocatorGet($name)
    {
        if ($name == 'BedRest.Dispatcher') {
            return $this->mockDispatcher;
        } elseif ($name == 'BedRest.ContentNegotiator') {
            return $this->mockNegotiator;
        }
    }

    /**
     * @test
     * @expectedException \Zend\Mvc\Exception\InvalidArgumentException
     */
    public function exceptionThrownIfNotAnHttpRequest()
    {
        $mockRequest = $this->getMock('Zend\Console\Request', array(), array(), '', false);
        $this->controller->dispatch($mockRequest);
    }

    /**
     * @test
     */
    public function resourceNameAvailable()
    {
        $this->controller->setResourceName('test-resource');
        $this->assertEquals('test-resource', $this->controller->getResourceName());
    }

    /**
     * @test
     */
    public function restRequestCreatedDuringDispatch()
    {
        $this->mockRequest->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue(Request::METHOD_POST));

        $this->controller->dispatch($this->mockRequest);

        $restRequest = $this->controller->getRestRequest();
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals(RestRequestType::METHOD_POST, $restRequest->getMethod());
    }

    /**
     * @test
     */
    public function identifierSetIfAvailable()
    {
        $identifier = 123;
        $this->mockRouteMatch->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->will($this->returnValue($identifier));

        $this->controller->dispatch($this->mockRequest);

        $restRequest = $this->controller->getRestRequest();
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals($identifier, $restRequest->getParameter('identifier'));
    }

    public function collectionRequests()
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
     * @dataProvider collectionRequests
     */
    public function collectionRequestCorrectlyIdentified($baseMethod, $id, $expectedMethod)
    {
        $this->mockRequest->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue($baseMethod));

        $this->mockRouteMatch->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->will($this->returnValue($id));

        $this->controller->dispatch($this->mockRequest);

        $restRequest = $this->controller->getRestRequest();
        $this->assertInstanceOf('BedRest\Rest\Request\Request', $restRequest);
        $this->assertEquals($expectedMethod, $restRequest->getMethod());
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

        $this->controller->dispatch($this->mockRequest);

        $restRequest = $this->controller->getRestRequest();
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

        $this->controller->dispatch($this->mockRequest);

        $restRequest = $this->controller->getRestRequest();
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

        $this->controller->dispatch($this->mockRequest);

        $restRequest = $this->controller->getRestRequest();
        $this->assertEquals(null, $restRequest->getContent());
    }

    /**
     * @test
     */
    public function onDispatchCallsDispatcher()
    {
        $this->controller->dispatch($this->mockRequest);

        $this->mockDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->controller->getRestRequest());

        $this->controller->onDispatch($this->mockEvent);
    }

    /**
     * @test
     */
    public function viewModelReturnedWithDataFromDispatcher()
    {
        $returnedData = array('content' => 'some data');

        $this->controller->dispatch($this->mockRequest);

        $this->mockDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->controller->getRestRequest())
            ->will($this->returnValue($returnedData));

        $result = $this->controller->onDispatch($this->mockEvent);

        $this->assertInstanceOf('BedRest\Framework\Zend2\View\Model\ViewModel', $result);
        $this->assertEquals($returnedData['content'], $result->getVariable('content'));
    }

    /**
     * @test
     */
    public function viewModelInjectedIntoEvent()
    {
        $this->controller->dispatch($this->mockRequest);

        $this->mockEvent->expects($this->once())
            ->method('setResult')
            ->with($this->isInstanceOf('BedRest\Framework\Zend2\View\Model\ViewModel'));

        $this->controller->onDispatch($this->mockEvent);
    }

    /**
     * @test
     */
    public function acceptInjectedIntoViewModel()
    {
        $this->controller->dispatch($this->mockRequest);
        $result = $this->controller->onDispatch($this->mockEvent);

        $this->assertEquals($this->controller->getRestRequest()->getAccept(), $result->getAccept());
    }
}
