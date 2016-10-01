# Instrument Middleware

[![Latest Version](https://img.shields.io/packagist/v/tuupola/instrument-middleware.svg?style=flat-square)](https://packagist.org/packages/tuupola/instrument-middleware)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/tuupola/instrument-middleware/master.svg?style=flat-square)](https://travis-ci.org/tuupola/instrument-middleware)
[![HHVM Status](https://img.shields.io/hhvm/tuupola/instrument-middleware.svg?style=flat-square)](http://hhvm.h4cc.de/package/tuupola/instrument-middleware)
[![Coverage](http://img.shields.io/codecov/c/github/tuupola/instrument-middleware.svg?style=flat-square)](https://codecov.io/github/tuupola/instrument-middleware)

Companion middleware for [Instrument](https://github.com/tuupola/instrument). Automates basic instrumenting of PSR-7 based application code.

![Instrument Middleware](http://www.appelsiini.net/img/instrument-middleware-1400.png)

## Install

Install using [composer](https://getcomposer.org/).

``` bash
$ composer require tuupola/instrument-middleware
```

## Usage

You must have access to [InfluxDB](https://influxdata.com/) database to store the data. Configure the [Instrument](https://github.com/tuupola/instrument) instance and pass it to the middleware. This is the only mandatory parameter. Heads up! The order of middlewares is important. Instrument middleware *must* be the last one added.

``` php
require __DIR__ . "/vendor/autoload.php";

$app = new \Slim\App;
$container = $app->getContainer();

$container["influxdb"] = function ($container) {
    return InfluxDB\Client::fromDSN("http+influxdb://foo:bar@localhost:8086/instrument");
};

$container["instrument"] = function ($container) {
    return new Instrument\Instrument([
        "adapter" => new Instrument\Adapter\InfluxDB($container["influxdb"]),
        "transformer" => new Instrument\Transformer\InfluxDB
    ]);
};

$container["instrumentMiddleware"] = function ($container) {
    return new Tuupola\Middleware\Instrument([
        "instrument" => $container["instrument"]
    ]);
};

$app->add("instrumentMiddleware");
```

## What data is logged?

Let's assume you have the following routes.

```php
$app->get("/", function ($request, $response, $arguments) {
    return $response->write("Here be dragons...\n");
});

$app->get("/hello/{name}", function ($request, $response, $arguments) {
    return $response->write("Hello {$arguments['name']}!\n");
});
```

When request is made the middleware saves basic instrumentation data to the database.

``` bash
$ curl http://192.168.50.53/
Here be dragons...
$ curl http://192.168.50.53/hello/foo
Hello foo!
```

``` sql
> select * from instrument
name: instrument
----------------
time                 bootstrap  memory   method  process  route          status  total
1475316633441185508  158        1048576  GET     53       /              200     213
1475316763025260932  140        1048576  GET     69       /hello/{name}  200     211

```

## Adding more data

You can also manually add additional data to the measurement.

```php
$app->get("/manual", function ($request, $response, $arguments) {
    $timing = $this->instrument->timing("instrument");
    $timing->start("db");
    /* Some expensive database queries. */
    $timing->stop("db");
    return $response->write("Manually adding additional data...\n");
});
````

``` bash
$ curl http://192.168.50.53/manual
Manually adding additional data...
```

``` sql
> select * from instrument
name: instrument
----------------
time                 bootstrap  db   memory   method  process  route    status  total
1475318315949095876  155        411  1048576  GET     466      /manual  200     623
```

## Testing

You can run tests either manually...

``` bash
$ vendor/bin/phpunit
$ vendor/bin/phpcs --standard=PSR2 src/ -p
```

... or automatically on every code change.

``` bash
$ npm install
$ grunt watch
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email tuupola@appelsiini.net instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
