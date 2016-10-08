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

namespace Tuupola\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Instrument
{
    //use \Witchcraft\Hydrate;
    use \Witchcraft\MagicMethods;
    use \Witchcraft\MagicProperties;

    private $options = [
        "measurement" => "instrument",
        "bootstrap" => "bootstrap",
        "total" => "total",
        "memory" => "memory",
        "status" => "status",
        "route" => "route",
        "method" => "method",
        "tags" => []
    ];

    private $settings;
    private $instrument = null;

    protected $logger;

    public function __construct($options)
    {
        /* Store passed in options overwriting any defaults. */
        $this->hydrate($options);

        if ($this->instrument) {
            //$this->instrument->tags($this->tags());
        }
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
        $bootstrap = (microtime(true) - $start) * 1000;
        $timing->set($this->bootstrap, (integer)$bootstrap);

        /* Call all the other middlewares. */
        $timing->start("process");
        $response = $next($request, $response);
        $timing->stop("process");

        /* Store PHP memory usage. */
        $timing->set($this->memory, (integer)$timing->memory());

        /* Store request method. */
        $timing->addTag($this->method, $request->getMethod());

        /* Store current route without query string. */
        $uri = $request->getUri();
        $timing->addTag($this->route, $uri->getPath());

        /* Store response status code. */
        $timing->addTag($this->status, $response->getStatusCode());

        /* Time spent from starting the request to exiting last middleware. */
        $total = (microtime(true) - $start) * 1000;
        $this
            ->instrument
            ->timing($this->measurement)
            ->set($this->total, (integer)$total);

        $this->instrument->send();

        return $response;
    }

    /**
     * Hydate options from given array
     *
     * @param array $data Array of options.
     * @return self
     */
    private function hydrate(array $data = [])
    {
        foreach ($data as $key => $value) {
            /* https://github.com/facebook/hhvm/issues/6368 */
            $key = str_replace(".", " ", $key);
            $method = "set" . ucwords($key);
            $method = str_replace(" ", "", $method);
            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }
        return $this;
    }

    public function setInstrument($instrument)
    {
        $this->instrument = $instrument;
        return $this;
    }

    public function setMeasurement($measurement)
    {
        $this->measurement = $measurement;
        return $this;
    }

    public function getMeasurement()
    {
        return $this->options["measurement"];
    }

    public function setBootstrap($bootstrap)
    {
        $this->options["bootstrap"] = $bootstrap;
        return $this;
    }

        public function getBootstrap()
    {
        return $this->options["bootstrap"];
    }

    public function setTotal($total)
    {
        $this->options["total"] = $total;
        return $this;
    }

    public function getTotal()
    {
        return $this->options["total"];
    }

    public function setMemory($memory)
    {
        $this->options["memory"] = $memory;
        return $this;
    }

    public function getMemory()
    {
        return $this->options["memory"];
    }

    public function setStatus($status)
    {
        $this->options["status"] = $statud;
        return $this;
    }

    public function getStatus()
    {
        return $this->options["status"];
    }

    public function setRoute($route)
    {
        $this->options["route"] = $route;
        return $this;
    }

    public function getRoute()
    {
        return $this->options["route"];
    }

    public function setMethod($method)
    {
        $this->options["method"] = $method;
        return $this;
    }

    public function getMethod()
    {
        return $this->options["method"];
    }

    public function setTags(array $tags)
    {
        $this->options["tags"] = $tags;
        return $this;
    }

    public function getTags()
    {
        return $this->options["tags"];
    }

    /**
     * Set the logger
     *
     * @return self
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Get the logger
     *
     * @return Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get the error handler
     *
     * @return string
     */
    public function getError()
    {
        return $this->options["error"];
    }

    /**
     * Set the error handler
     *
     * @return self
     */
    public function setError($error)
    {
        $this->options["error"] = $error;
        return $this;
    }

    /**
     * Call the error handler if it exists
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public function error(RequestInterface $request, ResponseInterface $response, $arguments)
    {
        if (is_callable($this->options["error"])) {
            $handler_response = $this->options["error"]($request, $response, $arguments);
            if (is_a($handler_response, "\Psr\Http\Message\ResponseInterface")) {
                return $handler_response;
            }
        }
        return $response;
    }
}
