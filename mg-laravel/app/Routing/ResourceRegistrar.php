<?php

namespace App\Routing;

use Illuminate\Routing\ResourceRegistrar as OriginalRegistrar;

class ResourceRegistrar extends OriginalRegistrar
{
    // add data to the array
    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    //protected $resourceDefaults = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'data'];
    protected $resourceDefaults = ['index', 'store', 'show', 'update', 'destroy', 'activate', 'inactivate', 'details'];

    protected function addResourceInactivate($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}/inativo';
        $action = $this->getResourceAction($name, $controller, 'inactivate', $options);
        return $this->router->match(['POST'], $uri, $action);
    }

    protected function addResourceActivate($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}/inativo';
        $action = $this->getResourceAction($name, $controller, 'activate', $options);
        return $this->router->match(['DELETE'], $uri, $action);
    }

    protected function addResourceDetails($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}/detalhes';
        $action = $this->getResourceAction($name, $controller, 'details', $options);
        return $this->router->match(['GET'], $uri, $action);
    }
}
