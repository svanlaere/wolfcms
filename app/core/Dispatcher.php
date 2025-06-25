<?php

/**
 * The Dispatcher class is responsible for mapping urls/routes to Controller methods.
 *
 * Each route that has the same number of directory components as the current
 * requested url is tried, and the first method that returns a response with a
 * non false/non null value will be returned via the Dispatcher::dispatch() method.
 *
 * For example:
 *
 * A route string can be a literal path such as '/pages/about' or can contain
 * wildcards (:any or :num) and/or regex like '/blog/:num' or '/page/:any'.
 *
 * <code>
 * Dispatcher::addRoute(array(
 *      '/' => 'page/index',
 *      '/about' => 'page/about,
 *      '/blog/:num' => 'blog/post/$1',
 *      '/blog/:num/comment/:num/delete' => 'blog/deleteComment/$1/$2'
 * ));
 * </code>
 *
 * Visiting /about/ would call PageController::about(),
 * visiting /blog/5 would call BlogController::post(5)
 * visiting /blog/5/comment/42/delete would call BlogController::deleteComment(5,42)
 *
 * The dispatcher is used by calling Dispatcher::addRoute() to setup the route(s),
 * and Dispatcher::dispatch() to handle the current request and get a response.
 */
final class Dispatcher {
    private static $routes = array();
    private static $params = array();
    private static $status = array();
    private static $requested_url = '';

    /**
     * Adds a route.
     *
     * @param string $route         A route string.
     * @param string $destination   Path that the request should be sent to.
     */
    public static function addRoute($route, $destination=null) {
        if ($destination != null && !is_array($route)) {
            $route = array($route => $destination);
        }
        self::$routes = array_merge(self::$routes, $route);
    }

    /**
     * Checks if a route exists for a specified path.
     *
     * @param string $path      A path (for instance path/to/page)
     * @return boolean          Returns true when a route was found, otherwise false.
     */
    public static function hasRoute($requested_url) {
        if (!self::$routes || count(self::$routes) == 0) {
            return false;
        }

        // Make sure we strip trailing slashes in the requested url
        $requested_url = rtrim($requested_url, '/');

        foreach (self::$routes as $route => $action) {
            // Convert wildcards to regex
            if (strpos($route, ':') !== false) {
                $route = str_replace(':any', '([^/]+)', str_replace(':num', '([0-9]+)', str_replace(':all', '(.+)', $route)));
            }

            // Does the regex match?
            if (preg_match('#^'.$route.'$#', $requested_url)) {
                // Do we have a back-reference?
                if (strpos($action, '$') !== false && strpos($route, '(') !== false) {
                    $action = preg_replace('#^'.$route.'$#', $action, $requested_url);
                }
                self::$params = self::splitUrl($action);
                // We found it, so we can break the loop now!
                return true;
            }
        }

        return false;
    }

    /**
     * Splits a URL into an array of its components.
     *
     * @param string $url   A URL.
     * @return array        An array of URL components.
     */
    public static function splitUrl($url) {
        return preg_split('/\//', $url, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Handles the request for a URL and provides a response.
     *
     * @param string $requested_url The URL that was requested.
     * @param string $default       Default URL to access if now URL was requested.
     * @return string               A response.
     */
    public static function dispatch($requested_url = null, $default = null) {
        Flash::init();

        // If no url passed, we will get the first key from the _GET array
        // that way, index.php?/controller/action/var1&email=example@example.com
        // requested_url will be equal to: /controller/action/var1
        if ($requested_url === null) {
            $pos = strpos($_SERVER['QUERY_STRING'], '&');
            if ($pos !== false) {
                $requested_url = substr($_SERVER['QUERY_STRING'], 0, $pos);
            } else {
                $requested_url = $_SERVER['QUERY_STRING'];
            }
        }

        // If no URL is requested (due to someone accessing admin section for the first time)
        // AND $default is set. Allow for a default tab.
        if ($requested_url == null && $default != null) {
            $requested_url = $default;
        }

        // Requested url MUST start with a slash (for route convention)
        if (strpos($requested_url, '/') !== 0) {
            $requested_url = '/' . $requested_url;
        }

        // Make sure we strip trailing slashes in the requested url
        $requested_url = rtrim($requested_url, '/');

        self::$requested_url = $requested_url;

        // This is only trace for debugging
        self::$status['requested_url'] = $requested_url;

        // Make the first split of the current requested_url
        self::$params = self::splitUrl($requested_url);

        // Do we even have any custom routing to deal with?
        if (count(self::$routes) === 0) {
            return self::executeAction(self::getController(), self::getAction(), self::getParams());
        }

        // Is there a literal match? If so we're done
        if (isset(self::$routes[$requested_url])) {
            self::$params = self::splitUrl(self::$routes[$requested_url]);
            return self::executeAction(self::getController(), self::getAction(), self::getParams());
        }

        // Loop through the route array looking for wildcards
        foreach (self::$routes as $route => $action) {
        // Convert wildcards to regex
            if (strpos($route, ':') !== false) {
                $route = str_replace(':any', '([^/]+)', str_replace(':num', '([0-9]+)', str_replace(':all', '(.+)', $route)));
            }
            // Does the regex match?
            if (preg_match('#^'.$route.'$#', $requested_url)) {
            // Do we have a back-reference?
                if (strpos($action, '$') !== false && strpos($route, '(') !== false) {
                    $action = preg_replace('#^'.$route.'$#', $action, $requested_url);
                }
                self::$params = self::splitUrl($action);
                // We found it, so we can break the loop now!
                break;
            }
        }

        return self::executeAction(self::getController(), self::getAction(), self::getParams());
    } // Dispatch

    /**
     * Returns the currently requested URL.
     *
     * @return string The currently requested URL.
     */
    public static function getCurrentUrl() {
        return self::$requested_url;
    }

    /**
     * Returns a reference to a controller class.
     *
     * @return string Reference to controller.
     */
    public static function getController() {
        // Check for settable default controller
        // if it's a plugin and not activated, revert to Wolf hardcoded default
        if (isset(self::$params[0]) && self::$params[0] == 'plugin' ) {
            $loaded_plugins = Plugin::$plugins;
            if (count(self::$params) < 2) {
                unset(self::$params[0]);
            } elseif (isset(self::$params[1]) && !isset($loaded_plugins[self::$params[1]])) {
                unset(self::$params[0]);
                unset(self::$params[1]);
            }
        }

        return isset(self::$params[0]) ? self::$params[0]: DEFAULT_CONTROLLER;
    }

    /**
     * Returns the action that was requested from a controller.
     *
     * @return string Reference to a controller's action.
     */
    public static function getAction() {
        return isset(self::$params[1]) ? self::$params[1]: DEFAULT_ACTION;
    }

    /**
     * Returns an array of parameters that should be passed to an action.
     *
     * @return array The action's parameters.
     */
    public static function getParams() {
        return array_slice(self::$params, 2);
    }

    /**
     * ???
     *
     * @todo Finish docblock
     *
     * @param <type> $key
     * @return <type>
     */
    public static function getStatus($key=null) {
        return ($key === null) ? self::$status: (isset(self::$status[$key]) ? self::$status[$key]: null);
    }

    /**
     * Executes a specified action for a specified controller class.
     *
     * @param string $controller
     * @param string $action
     * @param array $params
     */
    public static function executeAction($controller, $action, $params) {
        self::$status['controller'] = $controller;
        self::$status['action'] = $action;
        self::$status['params'] = implode(', ', $params);

        $controller_class = Inflector::camelize($controller);
        $controller_class_name = $controller_class . 'Controller';

        // Get an instance of that controller
        if (class_exists($controller_class_name)) {
            $controller = new $controller_class_name();
        } else {
        }
        if ( ! $controller instanceof Controller) {
            throw new Exception("Class '{$controller_class_name}' does not extends Controller class!");
        }

        // Execute the action
        $controller->execute($action, $params);
    }

} // end Dispatcher class