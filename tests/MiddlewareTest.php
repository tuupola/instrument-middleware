<?php

/*

Copyright (c) 2016-2022 Mika Tuupola

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

/**
 * @see       https://github.com/tuupola/instrument-middleware
 * @license   https://www.opensource.org/licenses/mit-license.php
 */

namespace Instrument;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;

class MiddlewareTest extends TestCase
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

        $toolkit = new Instrument([
            "transformer" => new \Instrument\Transformer\InfluxDB,
            "adapter" => new \Instrument\Adapter\Memory
        ]);

        $middleware = new Middleware([
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

    public function testShouldInvokeAlternative()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $response = new Response;

        $toolkit = new Instrument([
            "transformer" => new \Instrument\Transformer\InfluxDB,
            "adapter" => new \Instrument\Adapter\Memory
        ]);

        unset($_SERVER["REQUEST_TIME_FLOAT"]);

        $middleware = new Middleware([
            "instrument" => $toolkit,
            "method" => false,
            "status" => false,
            "process" => false,
            "route" => false,
            "bootstrap" => false,
            "memory" => false,
            "tags" => function ($request, $response) {
                return ["host" => "localhost"];
            }
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $middleware($request, $response, $next);

        $adapter = $toolkit->adapter();
        $sent = $adapter->measurements();

        /* instrument,host=localhost total=107i */
        $regexp = "/instrument,host=localhost total=\d*i/";
        $this->assertRegExp($regexp, (string)$sent["instrument"]);
    }

    public function testShouldSetAndGetInstrument()
    {
        $middleware = new Middleware([]);
        $this->assertEquals(null, $middleware->getInstrument());

        $middleware->setInstrument(new Instrument([]));
        $this->assertInstanceOf("Instrument\Instrument", $middleware->getInstrument());
    }


    public function testShouldSetAndGetMeasurement()
    {
        $middleware = new Middleware([]);
        $this->assertEquals("instrument", $middleware->getMeasurement());

        $middleware->setMeasurement("api");
        $this->assertEquals("api", $middleware->getMeasurement());
    }

    public function testShouldSetAndGetBootstrap()
    {
        $middleware = new Middleware([]);
        $this->assertEquals("bootstrap", $middleware->getBootstrap());

        $middleware->setBootstrap("setup");
        $this->assertEquals("setup", $middleware->getBootstrap());
    }

    public function testShouldSetAndGetProcess()
    {
        $middleware = new Middleware([]);
        $this->assertEquals("process", $middleware->getProcess());

        $middleware->setProcess("execute");
        $this->assertEquals("execute", $middleware->getProcess());
    }

    public function testShouldSetAndGetTotal()
    {
        $middleware = new Middleware([]);
        $this->assertEquals("total", $middleware->getTotal());

        $middleware->setTotal("combined");
        $this->assertEquals("combined", $middleware->getTotal());
    }

    public function testShouldSetAndGetMemory()
    {
        $middleware = new Middleware([]);
        $this->assertEquals("memory", $middleware->getMemory());

        $middleware->setMemory("mem");
        $this->assertEquals("mem", $middleware->getMemory());
    }

    public function testShouldSetAndGetStatus()
    {
        $middleware = new Middleware([]);
        $this->assertEquals("status", $middleware->getStatus());

        $middleware->setStatus("code");
        $this->assertEquals("code", $middleware->getStatus());
    }

    public function testShouldSetAndGetRoute()
    {
        $middleware = new Middleware([]);
        $this->assertEquals("route", $middleware->getRoute());

        $middleware->setRoute("uri");
        $this->assertEquals("uri", $middleware->getRoute());
    }

    public function testShouldSetAndGetMethod()
    {
        $middleware = new Middleware([]);
        $this->assertEquals("method", $middleware->getMethod());

        $middleware->setMethod("verb");
        $this->assertEquals("verb", $middleware->getMethod());
    }

    public function testShouldSetAndGetTags()
    {
        $middleware = new Middleware([]);

        $middleware->setTags(["mono" => "junk"]);
        $this->assertEquals(["mono" => "junk"], $middleware->getTags());

        $middleware->setTags(function () {
            return ["foo" => "bar"];
        });
        $tags = $middleware->getTags();
        $this->assertEquals(["foo" => "bar"], $tags());
    }
}
