<?php

namespace SuperV\Platform\Domains\Port;

use SuperV\Platform\Support\Concerns\Hydratable;

class Port
{
    use Hydratable;

    protected $slug;

    protected $hostname;

    protected $secure = false;

    protected $baseUrl = '/';

    protected $roles = [];

    protected $model;

    protected $middlewares;

    protected $guard;

    protected $composers;

    protected $navigationSlug;

    public function __construct(array $attributes = [])
    {
        $this->hydrate($attributes);
    }

    public function slug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function baseUrl()
    {
        if (! starts_with($this->baseUrl, '/')) {
            return '/'.$this->baseUrl;
        }

        return $this->baseUrl;
    }

    public function root()
    {
        return $this->hostname().($this->baseUrl() !== '/' ? $this->baseUrl() : '');
    }

    public function hostname()
    {
        return $this->hostname ?? sv_config('hostname');
    }

    public function url()
    {
        return $this->scheme().'://'.$this->root();
    }

    public function roles()
    {
        return $this->roles;
    }

    public function middlewares()
    {
        return $this->middlewares;
    }

    public function guard()
    {
        return $this->guard;
    }

    public function getComposers()
    {
        return $this->composers;
    }

    public function getNavigationSlug()
    {
        return $this->navigationSlug;
    }

    public function isSecure()
    {
        return $this->secure;
    }

    public function setNavigationSlug($navigationSlug): void
    {
        $this->navigationSlug = $navigationSlug;
    }

    public function scheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }
}