<?php namespace SuperV\Platform\Domains\Droplet;

use SuperV\Platform\Domains\Droplet\Model\DropletCollection;
use SuperV\Platform\Domains\Droplet\Model\DropletModel;

class DropletIntegrator
{
    /**
     * @var DropletProvider
     */
    private $provider;
    /**
     * @var DropletCollection
     */
    private $droplets;
    
    public function __construct(DropletProvider $provider, DropletCollection $droplets)
    {
        $this->provider = $provider;
        $this->droplets = $droplets;
    }
    
    public function register(DropletModel $model)
    {
//        $class = $model->namespace . '\\' . studly_case($model->name) . studly_case($model->type);

        $class = $model->droplet();

        /** @var Droplet $droplet */
        $droplet = app($class)->setModel($model);
        
        $this->provider->register($droplet);
        $this->droplets->put($droplet->getSlug(), $droplet);
    }
}