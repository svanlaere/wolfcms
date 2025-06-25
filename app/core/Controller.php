<?php

/**
 * A main controller class to be subclassed.
 *
 * The Controller class should be the parent class of all of your Controller
 * sub classes which contain the business logic of your application like:
 *      - render a blog post,
 *      - log a user in,
 *      - delete something and redirect,
 *      - etc.
 *
 * Using the Dispatcher class you can define what paths/routes map to which
 * Controllers and their methods.
 *
 * Each Controller method should either:
 *      - return a string response
 *      - redirect to another method
 */
class Controller {
    protected $layout = false;
    protected $layout_vars = array();

    /**
     * Executes a specified action/method for this Controller.
     *
     * @param string $action
     * @param array $params
     */
    public function execute($action, $params) {
    // it's a private method of the class or action is not a method of the class
        if (substr($action, 0, 1) == '_' || ! method_exists($this, $action)) {
            throw new Exception("Action '{$action}' is not valid!");
        }
        call_user_func_array(array($this, $action), $params);
    }

    /**
     * Sets which layout to use for output.
     *
     * @param string $layout
     */
    public function setLayout($layout) {
        $this->layout = $layout;
    }

    /**
     * Assigns a set of key/values pairs to a layout.
     *
     * @param mixed $var    An array of key/value pairs or the name of a single variable.
     * @param string $value The value of the single variable.
     */
    public function assignToLayout($var, $value = null) {
        if (is_array($var)) {
            $this->layout_vars = array_merge($this->layout_vars, $var);
        } else {
            $this->layout_vars[$var] = $value;
        }
    }

    /**
     * Renders the output.
     *
     * @todo Remove? Is this proper OO/good idea?
     *
     * @param string $view  Name of the view to render
     * @param array $vars   Array of variables
     * @return View
     */
    public function render($view, $vars=array()) {
        if ($this->layout) {
            $this->layout_vars['content_for_layout'] = new View($view, $vars);
            return new View('../layouts/'.$this->layout, $this->layout_vars);
        } else {
            return new View($view, $vars);
        }
    }

    /**
     * Displays a rendered layout.
     *
     * @todo Remove? Is this proper OO/good idea?
     *
     * @param <type> $view
     * @param <type> $vars
     * @param <type> $exit
     */
    public function display($view, $vars=array(), $exit=true) {
        echo $this->render($view, $vars);

        if ($exit) exit;
    }

    /**
     * Renders a JSON encoded response and returns that as a string
     *
     * @param mixed $data_to_encode The data being encoded.
     * @return string               The JSON representation of $data_to_encode.
     */
    public function renderJSON($data_to_encode) {
        if (class_exists('JSON')) {
            return JSON::encode($data_to_encode);
        } else if (function_exists('json_encode')) {
                return json_encode($data_to_encode);
            } else {
                throw new Exception('No function or class found to render JSON.');
            }
    }

} // end Controller class