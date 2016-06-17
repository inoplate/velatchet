<?php 

namespace Inoplate\Velatchet;

use Illuminate\Contracts\Container\Container as ContainerContract;

class TopicHandlers
{
    /**
     * @var Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $handlers = [];

    /**
     * Create new TopicHandlers
     * 
     * @param ContainerContract $container
     */
    public function __construct(ContainerContract $container)
    {
        $this->container = $container;
    }

    /**
     * Register topic handler
     * 
     * @param  string              $topic   
     * @param  callable|string     $handler 
     * @return void
     */
    public function register($topic, $handler)
    {
        $this->handlers[$topic] = $this->makeHandler($handler);
    }

    /**
     * Retrieve topic handler
     * 
     * @param  string $topic
     * @return callable
     */
    public function getHandler($topic)
    {
        return isset($this->handlers[$topic]) ? $this->handlers[$topic] : null;
    }

    /**
     * Make handler
     * 
     * @param  string|callable  $handler
     * @return callable
     */
    protected function makeHandler($handler)
    {
        return is_string($handler) ? $this->createClassHandler($handler) : $handler;
    }

    /**
     * Create handler from class
     * 
     * @param  string    $handler
     * @return callable
     */
    protected function createClassHandler($handler)
    {
        $container = $this->container;

        return function () use ($handler, $container) {
            return call_user_func_array(
                $this->createClassCallable($handler, $container), func_get_args()
            );
        };
    }

    /**
     * Transform class to callable
     * 
     * @param  string|callable  $handler
     * @param  Container        $container
     * @return callable
     */
    protected function createClassCallable($handler, $container)
    {
        list($class, $method) = $this->parseClassCallable($handler);
        
        return [$container->make($class), $method];
    }

    /**
     * Parse the class listener into class and method.
     *
     * @param  string  $listener
     * @return array
     */
    protected function parseClassCallable($handler)
    {
        $segments = explode('@', $handler);

        return [$segments[0], count($segments) == 2 ? $segments[1] : 'handle'];
    }
}