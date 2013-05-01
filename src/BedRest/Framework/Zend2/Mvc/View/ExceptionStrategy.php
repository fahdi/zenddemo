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

namespace BedRest\Framework\Zend2\Mvc\View;

use BedRest\Content\Negotiation\MediaTypeList;
use BedRest\Framework\Zend2\View\Model\ViewModel;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;

/**
 * ExceptionStrategy
 *
 * @author Geoff Adams <geoff@dianode.net>
 */
class ExceptionStrategy implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * @var boolean
     */
    protected $displayExceptions;

    /**
     * @param boolean $displayExceptions
     */
    public function setDisplayExceptions($displayExceptions)
    {
        $this->displayExceptions = $displayExceptions;
    }

    /**
     * @return boolean
     */
    public function getDisplayExceptions()
    {
        return $this->displayExceptions;
    }

    /**
     * @param \Zend\EventManager\EventManagerInterface $events
     * @param int                                      $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_RENDER_ERROR,
            array($this, 'forceFallbackViewModel'),
            $priority
        );
    }

    /**
     * @param \Zend\EventManager\EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    public function forceFallbackViewModel(MvcEvent $e)
    {
        $data = array(
            'message' => 'An error occurred during execution; please try again later.',
        );

        if ($this->displayExceptions) {
            /** @var \Exception $ex */
            $ex = $e->getParam('exception');
            $data['exception'] = array(
                'type' => get_class($ex),
                'code' => $ex->getCode(),
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'trace' => $ex->getTrace()
            );
        }

        $model = new ViewModel(array(
            'data' => $data
        ));

        // TODO: allow the fallback content type to be specified in configuration
        $model->setAccept(new MediaTypeList(array('application/json')));

        $e->setResult($model);

        // we reset this here to ensure the default ExceptionStrategy doesn't simply override the work done in here
        // see http://stackoverflow.com/questions/12693762/define-a-custom-exceptionstrategy-in-a-zf2-module
        $e->setError(false);

        /** @var \Zend\Http\Response $response */
        $response = $e->getResponse();

        if (!$response) {
            $response = new HttpResponse();
            $response->setStatusCode(500);
            $e->setResponse($response);
        } else {
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $response->setStatusCode(500);
            }
        }

        $e->setResponse($response);
    }
}
