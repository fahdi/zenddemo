<?php

namespace BedRest\Tests\Framework\Zend2\Mvc\Controller;

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
    protected $mockRestRequest;

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
        $this->mockRestRequest = $this->getMock('BedRest\Rest\Request\Request');

        $this->mockRequest = $this->getMockBuilder('Zend\Http\Request')
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
        } elseif ($name == 'BedRest.Request') {
            return $this->mockRestRequest;
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
        $this->assertEquals($this->mockRestRequest, $restRequest);
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
