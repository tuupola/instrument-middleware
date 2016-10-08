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

use Zend\Diactoros\Request;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;

use Instrument\Instrument as InstrumentToolkit;
use Tuupola\Middleware\Instrument as InstrumentMiddleware;

class InstrumentTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldBeTrue()
    {
        $this->assertTrue(true);
    }

    public function testShouldInvoke()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $response = new Response;

        $toolkit = new InstrumentToolkit([
            "transformer" => new \Instrument\Transformer\InfluxDB,
            "adapter" => new \Instrument\Adapter\Memory
        ]);

        $middleware = new InstrumentMiddleware([
            "instrument" => $toolkit
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $middleware($request, $response, $next);

        $adapter = $toolkit->adapter();
        $sent = $adapter->measurements();

        /* instrument,method=GET,route=/api,status=200 bootstrap=105i,process=10i,memory=7864320i,total=107i */
        $regexp = "/instrument,method=GET,route=\/api,status=200 bootstrap=\d*i,process=\d*i,memory=\d*i,total=\d*i/";
        $this->assertRegExp($regexp, (string)$sent["instrument"]);
    }


    public function testShouldSetAndGetInstrument()
    {
        $middleware = new InstrumentMiddleware([]);
        $this->assertEquals(null, $middleware->getInstrument());

        $middleware->setInstrument(new InstrumentToolkit([]));
        $this->assertInstanceOf("Instrument\Instrument", $middleware->getInstrument());
    }


    public function testShouldSetAndGetMeasurement()
    {
        $middleware = new InstrumentMiddleware([]);
        $this->assertEquals("instrument", $middleware->getMeasurement());

        $middleware->setMeasurement("api");
        $this->assertEquals("api", $middleware->getMeasurement());
    }

    public function testShouldSetAndGetBootstrap()
    {
        $middleware = new InstrumentMiddleware([]);
        $this->assertEquals("bootstrap", $middleware->getBootstrap());

        $middleware->setBootstrap("setup");
        $this->assertEquals("setup", $middleware->getBootstrap());
    }

    public function testShouldSetAndGetTotal()
    {
        $middleware = new InstrumentMiddleware([]);
        $this->assertEquals("total", $middleware->getTotal());

        $middleware->setTotal("combined");
        $this->assertEquals("combined", $middleware->getTotal());
    }

    public function testShouldSetAndGetMemory()
    {
        $middleware = new InstrumentMiddleware([]);
        $this->assertEquals("memory", $middleware->getMemory());

        $middleware->setMemory("mem");
        $this->assertEquals("mem", $middleware->getMemory());
    }

    public function testShouldSetAndGetStatus()
    {
        $middleware = new InstrumentMiddleware([]);
        $this->assertEquals("status", $middleware->getStatus());

        $middleware->setStatus("code");
        $this->assertEquals("code", $middleware->getStatus());
    }

    public function testShouldSetAndGetRoute()
    {
        $middleware = new InstrumentMiddleware([]);
        $this->assertEquals("route", $middleware->getRoute());

        $middleware->setRoute("uri");
        $this->assertEquals("uri", $middleware->getRoute());
    }

    public function testShouldSetAndGetMethod()
    {
        $middleware = new InstrumentMiddleware([]);
        $this->assertEquals("method", $middleware->getMethod());

        $middleware->setMethod("verb");
        $this->assertEquals("verb", $middleware->getMethod());
    }

    public function testShouldSetAndGetTags()
    {
        $middleware = new InstrumentMiddleware([]);

        $middleware->setTags(["mono" => "junk"]);
        $this->assertEquals(["mono" => "junk"], $middleware->getTags());

        $middleware->setTags(function () {
            return ["foo" => "bar"];
        });
        $tags = $middleware->getTags();
        $this->assertEquals(["foo" => "bar"], $tags());
    }
}
