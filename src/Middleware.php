<?php

/*
 * This file is part of the Instrument middleware package
 *
 * Copyright (c) 2016 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/instrument-middleware
 *
 */

namespace Instrument;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Middleware
{
    use \Witchcraft\Hydrate;
    use \Witchcraft\MagicMethods;

    private $instrument = null;
    private $measurement = "instrument";
    private $bootstrap = "bootstrap";
    private $process = "process";
    private $total = "total";
    private $memory = "memory";
    private $status = "status";
    private $route = "route";
    private $method = "method";
    private $tags = [];

    public function __construct($options)
    {
        /* Store passed in options overwriting any defaults. */
        $this->hydrate($options);
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        /* REQUEST_TIME_FLOAT is closer to truth. */
        if (isset($_SERVER["REQUEST_TIME_FLOAT"])) {
            $start = $_SERVER["REQUEST_TIME_FLOAT"];
        } else {
            $start = microtime(true);
        }

        $timing = $this->instrument->timing($this->measurement);

        /* Time spent from starting the request to entering first middleware. */
        if ($this->bootstrap) {
            $bootstrap = (microtime(true) - $start) * 1000;
            $timing->set($this->bootstrap, (integer)$bootstrap);
        }

        /* Call all the other middlewares. */
        if ($this->process) {
            $timing->start($this->process);
            $response = $next($request, $response);
            $timing->stop($this->process);
        }

        /* Store PHP memory usage. */
        if ($this->memory) {
            $timing->set($this->memory, (integer)$timing->memory());
        }

        /* Store request method. */
        if ($this->method) {
            $timing->addTag($this->method, $request->getMethod());
        }

        /* Store current route without query string. */
        if ($this->route) {
            $uri = $request->getUri();
            $timing->addTag($this->route, $uri->getPath());
        }

        /* Store response status code. */
        if ($this->status) {
            $timing->addTag($this->status, $response->getStatusCode());
        }

        /* Add the tags which are passed in options. This will overwrite */
        /* any of the default tags. */
        if (is_array($this->tags)) {
            $timing->addTags($this->tags);
        } else {
            $timing->addTags($this->tags($request, $response));
        }

        /* Time spent from starting the request to exiting last middleware. */
        if ($this->total) {
            $total = (microtime(true) - $start) * 1000;
            $timing->set($this->total, (integer)$total);
        }

        $this->instrument->send();

        return $response;
    }

    public function setInstrument(Instrument $instrument)
    {
        $this->instrument = $instrument;
        return $this;
    }

    public function getInstrument()
    {
        return $this->instrument;
    }

    public function setMeasurement($measurement)
    {
        $this->measurement = $measurement;
        return $this;
    }

    public function getMeasurement()
    {
        return $this->measurement;
    }

    public function setBootstrap($bootstrap)
    {
        $this->bootstrap = $bootstrap;
        return $this;
    }

    public function getBootstrap()
    {
        return $this->bootstrap;
    }

    public function setProcess($process)
    {
        $this->process = $process;
        return $this;
    }

    public function getProcess()
    {
        return $this->process;
    }

    public function setTotal($total)
    {
        $this->total = $total;
        return $this;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function setMemory($memory)
    {
        $this->memory = $memory;
        return $this;
    }

    public function getMemory()
    {
        return $this->memory;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setRoute($route)
    {
        $this->route = $route;
        return $this;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setTags($tags)
    {
        $this->tags = $tags;
        return $this;
    }

    public function getTags()
    {
        return $this->tags;
    }
}
