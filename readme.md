# phputil/router

> Express-like router for PHP

- Fast ⚡
- Unit-tested ✅

_Warning: This router is under development. Do not use it in production yet._

## Installation

```bash
composer require phputil/router
```

## Features

- [✔] Support to standard HTTP methods (`GET`, `POST`, `PUT`, `DELETE`, `HEAD`, `OPTIONS`) and `PATCH`.
- [✔] Route parameters
    - _e.g._ `$app->get('/customers/:id', function( $req, $res ) { $res->send( $req->param('id') ); } );`
- [✔] URL groups
    - _e.g._ `$app->route('/customers/:id')->get('/emails', $cbGetEmails );`
- [✔] Global middlewares
    - _e.g._ `$app->use( function( $req, $res, &$stop ) { /*...*/ } );`
- [✔] Middlewares per URL group
    - _e.g._ `$app->route( '/admin' )->use( $middlewareIsAdmin )->get( '/', function( $req, $res ) { /*...*/ } );`
- [✔] Middlewares per route
    - _e.g._ `$app->get( '/', $middleware1, $middleware2, function( $req, $res ) { /*...*/ } );`
- [✔] Request cookies
    - _e.g._ `$app->get('/', function( $req, $res ) { $res->send( $req->cookie('sid') ); } );`
- [✔] _Extra_: Embedded CORS middleware.
- [✔] _Extra_: Can mock HTTP requests for testing, without needing to run an HTTP server.
- [🕑] _(soon)_ Deal with `multipart/form-data` on `PUT` and `PATCH`


**Notes about `phputil/router`**:

1. Unlike Express, `phputil/router` needs an HTTP server to run (if the request is not [mocked](#mocking-an-http-request)). You can use the HTTP server of your choice, such as `php -S localhost:80`, Apache, Nginx or [http-server](https://www.npmjs.com/package/http-server).

2. The library does not aim to cover the entire [Express API](https://expressjs.com/en/api.html). However, feel free to contribute to this project and add more features.

## Examples

### Hello World

```php
require_once 'vendor/autoload.php';

$app = new \phputil\router\Router();
$app->get('/', function( $req, $res ) {
    $res->send( 'Hello World!' );
} )
$app->listen();
```

### Saying Hi

```php
require_once 'vendor/autoload.php';

$app = new \phputil\router\Router();
$app->route( '/hi' )
    ->get('/', function( $req, $res ) {
        $res->send( 'Hi, Anonymous' );
    } )
    ->get('/:name', function( $req, $res ) {
        $res->send( 'Hi, ' . $req->param( 'name' ) );
    } );
$app->listen();
```

### Middleware per route

```php
require_once 'vendor/autoload.php';

$middlewareIsAdmin = function( $req, $res, &$stop ) {
    session_start();
    $isAdmin = isset( $_SESSION[ 'admin' ] ) && $_SESSION[ 'admin' ];
    if ( $isAdmin ) {
        return; // Allowed
    }
    $stop = true;
    $res->status( 403 )->end(); // Forbidden
};

$app = new \phputil\router\Router();
$app->get( '/admin', $middlewareIsAdmin, function( $req, $res ) {
    $res->send( 'Hello, admin' );
} );
$app->listen();
```

### CRUD with JSON

```php
<?php
require_once 'vendor/autoload.php';

$tasks = [ // Some data for the example
    ['id'=>1, 'what'=>'Buy beer'],
    ['id'=>2, 'what'=>'Wash the dishes']
];

function generateId( $arrayCopy ) { // Just for the example
    $last = end( $arrayCopy );
    return isset( $last, $last['id'] ) ? 1 + $last['id'] : 1;
}

$app = new \phputil\router\Router();
$app->route( '/tasks' )
    ->get( '/', function( $req, $res ) use ( &$tasks ) {
        $res->json( $tasks );
    } )
    ->post( '/', function( $req, $res ) use ( &$tasks ) {
        $t = (array) json_encode( $req->rawBody() );
        $t['id'] = generateId( $tasks );
        $tasks []= $t;
        $res->status( 201 )->send( $t['id'] ); // Created
    } )
    ->get( '/:id', function( $req, $res ) use ( &$tasks ) {
        $key = array_search( $req->param( 'id' ), array_column( $tasks, 'id' ) );
        if ( $key === false ) {
            return $res->status( 404 )->send( 'Not Found' );
        }
        $res->json( $tasks[ $key ] );
    } )
    ->delete( '/:id', function( $req, $res ) use ( &$tasks ) {
        $key = array_search( $req->param( 'id' ), array_column( $tasks, 'id' ) );
        if ( $key === false ) {
            return $res->status( 404 )->send( 'Not Found' );
        }
        unset( $tasks[ $key ] ); // Remove
        $res->status( 204 )->end(); // No Content
    } )
    ->put( '/:id', function( $req, $res ) use ( &$tasks ) {
        $key = array_search( $req->param( 'id' ), array_column( $tasks, 'id' ) );
        if ( $key === false ) {
            return $res->status( 404 )->send( 'Not Found' );
        }
        $t = (array) json_encode( $req->rawBody() );
        $tasks[ $key ] = $t;
        $res->end();
    } );

$app->listen();
```

## Known Middlewares

Did you create a useful middleware? Open an Issue to include it here.

## API

_Soon_

### Mocking an HTTP request

```php
require_once 'vendor/autoload.php';
use \phputil\router\FakeHttpRequest;
use \phputil\router\Router;

$fakeReq = new FakeHttpRequest();
$fakeReq->withURL( '/foo' )->withMethod( 'GET' );

$app = new Router();
$app->get( '/foo', function( $req, $res ) { $res->send( 'Called!' ); } );
$app->listen( [ 'req' => $fakeReq ] ); // It will use the fake request and call /foo
```

## License

[MIT](LICENSE) © [Thiago Delgado Pinto](https://github.com/thiagodp)
