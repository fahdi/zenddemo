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

namespace BedRest\Framework\Zend2\View\Strategy;

use BedRest\Content\Negotiation\NegotiatedResult;
use BedRest\Framework\Zend2\View\Model\ViewModel;
use BedRest\Framework\Zend2\View\Renderer\ContentNegotiationRenderer;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\View\ViewEvent;

/**
 * ContentNegotiationStrategy
 *
 * @author Geoff Adams <geoff@dianode.net>
 */
class ContentNegotiationStrategy implements ListenerAggregateInterface
{
    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * @var \BedRest\Framework\Zend2\View\Renderer\ContentNegotiationRenderer
     */
    protected $renderer;

    /**
     * @param \BedRest\Framework\Zend2\View\Renderer\ContentNegotiationRenderer $renderer
     */
    public function __construct(ContentNegotiationRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @param \Zend\EventManager\EventManagerInterface $events
     * @param int                                      $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER, array($this, 'selectRenderer'), $priority);
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RESPONSE, array($this, 'injectResponse'), $priority);
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

    /**
     * @param  \Zend\View\ViewEvent                                              $e
     * @return \BedRest\Framework\Zend2\View\Renderer\ContentNegotiationRenderer
     */
    public function selectRenderer(ViewEvent $e)
    {
        if (!$e->getModel() instanceof ViewModel) {
            return;
        }

        return $this->renderer;
    }

    /**
     * Inject the response with the JSON payload and appropriate Content-Type header
     *
     * @param  \Zend\View\ViewEvent $e
     * @return void
     */
    public function injectResponse(ViewEvent $e)
    {
        $renderer = $e->getRenderer();
        if ($renderer !== $this->renderer) {
            // Discovered renderer is not ours; do nothing
            return;
        }

        $result   = $e->getResult();
        if (!$result instanceof NegotiatedResult) {
            // not a NegotiatedResult, we can't go on here
            return;
        }

        // Populate response
        $response = $e->getResponse();
        $response->setContent($result->content);

        $headers = $response->getHeaders();
        $headers->addHeaderLine('content-type', $result->contentType);
    }
}
