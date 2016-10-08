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

class InstrumentTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldBeTrue()
    {
        $this->assertTrue(true);
    }

    /*
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
    */

    public function testShouldSetAndGetMeasurement()
    {
        $middleware = new Instrument([]);
        $this->assertEquals("instrument", $middleware->getMeasurement());

        $middleware->setMeasurement("api");
        $this->assertEquals("api", $middleware->getMeasurement());
    }

    public function testShouldSetAndGetBootstrap()
    {
        $middleware = new Instrument([]);
        $this->assertEquals("bootstrap", $middleware->getBootstrap());

        $middleware->setBootstrap("setup");
        $this->assertEquals("setup", $middleware->getBootstrap());
    }

    public function testShouldSetAndGetTotal()
    {
        $middleware = new Instrument([]);
        $this->assertEquals("total", $middleware->getTotal());

        $middleware->setTotal("combined");
        $this->assertEquals("combined", $middleware->getTotal());
    }

    public function testShouldSetAndGetMemory()
    {
        $middleware = new Instrument([]);
        $this->assertEquals("memory", $middleware->getMemory());

        $middleware->setMemory("mem");
        $this->assertEquals("mem", $middleware->getMemory());
    }

    public function testShouldSetAndGetStatus()
    {
        $middleware = new Instrument([]);
        $this->assertEquals("status", $middleware->getStatus());

        $middleware->setStatus("code");
        $this->assertEquals("code", $middleware->getStatus());
    }

    public function testShouldSetAndGetRoute()
    {
        $middleware = new Instrument([]);
        $this->assertEquals("route", $middleware->getRoute());

        $middleware->setRoute("uri");
        $this->assertEquals("uri", $middleware->getRoute());
    }

    public function testShouldSetAndGetMethod()
    {
        $middleware = new Instrument([]);
        $this->assertEquals("method", $middleware->getMethod());

        $middleware->setMethod("verb");
        $this->assertEquals("verb", $middleware->getMethod());
    }

    public function testShouldSetAndGetTags()
    {
        $middleware = new Instrument([]);

        $middleware->setTags(["mono" => "junk"]);
        $this->assertEquals(["mono" => "junk"], $middleware->getTags());

        $middleware->setTags(function () {
            return ["foo" => "bar"];
        });
        $tags = $middleware->getTags();
        $this->assertEquals(["foo" => "bar"], $tags());
    }
}
