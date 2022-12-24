<?php
namespace phputil\router;

const METHOD_GET        = 'GET';
const METHOD_POST       = 'POST';
const METHOD_PUT        = 'PUT';
const METHOD_DELETE     = 'DELETE';
const METHOD_OPTIONS    = 'OPTIONS';
const METHOD_HEAD       = 'HEAD';
const METHOD_PATCH      = 'PATCH';

const SUPPORTED_METHODS = [
    METHOD_GET,
    METHOD_POST,
    METHOD_PUT,
    METHOD_DELETE,
    METHOD_OPTIONS,
    METHOD_HEAD,
    METHOD_PATCH
];


const ENTRY_HTTP        = 'h';
const ENTRY_GROUP       = 'g';
const ENTRY_MIDDLEWARE  = 'm';

interface Entry {
    function type();
}

class MiddlewareEntry implements Entry {

    /** @var callback Callback like function ( $req, $res, &$stop ) */
    public $callback = null;

    public function __construct( $callback ) {
        $this->callback = $callback;
    }

    function type() {
        return ENTRY_MIDDLEWARE;
    }
}


abstract class RouteBasedEntry implements Entry {

    /** @var string */
    public $route = '';

    /** @var Entry */
    public $parent = null;

    /** @var array of Entry */
    public $children = [];

    /** @var bool */
    public $isGroup = false;

    function __construct( $route ) {
        $this->route = $route;
    }

    /** @inheritDoc */
    abstract function type();


    function withParent( $parent ) {
        $this->parent = $parent;
        return $this;
    }

    function withRoute( $parent ) {
        $this->parent = $parent;
        return $this;
    }

    function hasParent() {
        return $this->parent != null;
    }

    function end() {
        return $this->hasParent() ? $this->parent : $this;
    }

    // function extractRoute( array &$target ) {
    //     if ( ! $this->isGroup ) {
    //         $target []= $this->route;
    //         return;
    //     }
    //     $this->extractRoutes( $target, $this->children, $this->route );
    // }

    // protected function extractRoutes( array &$target, array $children, $lastRoute ) {
    //     foreach ( $children as $child ) {
    //         $r = joinRoutes( [ $lastRoute, $child->route ] );
    //         if ( ! $child->isGroup ) {
    //             $target []= $r;
    //         } else {
    //             $this->extractRoutes( $target, $child->children, $r );
    //         }
    //     }
    // }

}


class HttpEntry extends RouteBasedEntry {

    public $httpMethod = METHOD_GET;
    public $callback = null;

    function __construct( $route, $httpMethod, $callback = null ) {
        parent::__construct( $route );
        $this->httpMethod = $httpMethod;
        $this->callback = $callback;
    }

    /** @inheritDoc */
    function type() {
        return ENTRY_HTTP;
    }
}


class GroupEntry extends RouteBasedEntry {

    function __construct( $route ) {
        parent::__construct( $route );
        $this->isGroup = true;
    }

    /** @inheritDoc */
    function type() {
        return ENTRY_GROUP;
    }

    // HTTP

    function get( $route, $callback = null ) {
        return $this->addEntry( $route, METHOD_GET, $callback );
    }

    function post( $route, $callback = null ) {
        return $this->addEntry( $route, METHOD_POST, $callback );
    }

    function put( $route, $callback = null ) {
        return $this->addEntry( $route, METHOD_PUT, $callback );
    }

    function delete( $route, $callback = null ) {
        return $this->addEntry( $route, METHOD_DELETE, $callback );
    }

    function patch( $route, $callback = null ) {
        return $this->addEntry( $route, METHOD_PATCH, $callback );
    }

    function options( $route, $callback = null ) {
        return $this->addEntry( $route, METHOD_OPTIONS, $callback );
    }

    function head( $route, $callback = null ) {
        return $this->addEntry( $route, METHOD_HEAD, $callback );
    }

    function all( $route, $callback = null ) {
        foreach ( SUPPORTED_METHODS as $method ) {
            $this->addEntry( $method, $route, $callback );
        }
        return $this;
    }

    protected function addEntry( $route, $httpMethod, $callback = null ) {
        if ( ! isHttpMethodValid( $httpMethod ) ) {
            throw new \LogicException( "Invalid HTTP method: $httpMethod" );
        }
        if ( \is_array( $route ) ) {
            foreach ( $route as $str ) {
                $this->children []= new HttpEntry( $str, $httpMethod, $callback );
            }
        } else {
            $this->children []= new HttpEntry( $route, $httpMethod, $callback );
        }
        return $this;
    }

    // SUB-GROUP

    function group( $route ) {
        $g = new GroupEntry( $route );
        $g->parent = $this;
        $this->children []= $g;
        return $g;
    }

    /** Alias to group() */
    function route( $route ) {
        return $this->group( $route );
    }

    // MIDDLEWARE

    function use( $callback ) {
        $this->children []= new MiddlewareEntry( $callback );
        return $this;
    }

    // BACK TO THE PARENT

    function end() {
        return $this->parent === null ? $this : $this->parent;
    }
}

/**
 * Join routes in a single URL path.
 */
function joinRoutes( array $routes ) {
    return \str_replace( '//', '/', \implode( '/', $routes ) );
}

?>
