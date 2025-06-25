<?php

/**
 * Flash service.
 *
 * The purpose of this service is to make some data available across pages.
 * Flash data is available on the next page but deleted when execution reaches
 * its end.
 *
 * Usual use of Flash is to make it possible for the current page to pass some
 * data to the next one (for instance success or error message before an HTTP
 * redirect).
 *
 * Example usage:
 * <code>
 *      Flash::set('errors', 'Blog not found!');
 *      Flash::set('success', 'Blog has been saved with success!');
 *      Flash::get('success');
 * </code>
 *
 * The Flash service as a concept is taken from Rails.
 */
final class Flash {
    const SESSION_KEY = 'framework_flash';

    private static $_flashstore = array(); // Data that prevous page left in the Flash

    /**
     * Returns a specific variable from the Flash service.
     *
     * If the value is not found, NULL is returned instead.
     * @todo Return false instead?
     *
     * @param string $var   Variable name
     * @return mixed        Value of the variable stored in the Flash service.
     */
    public static function get($var) {
        return isset(self::$_flashstore[$var]) ? self::$_flashstore[$var] : null;
    }

    /**
     * Adds specific variable to the Flash service.
     *
     * This variable will be available on the next page unless removed with the
     * removeVariable() or clear() methods.
     *
     * @param string $var   Variable name
     * @param mixed $value  Variable value
     */
    public static function set($var, $value) {
        $_SESSION[self::SESSION_KEY][$var] = $value;
    }

    /**
     * Adds specific variable to the Flash service.
     *
     * This variable will be available on the current page only.
     *
     * @param string $var   Variable name
     * @param mixed $value  Variable value
     */
    public static function setNow($var, $value) {
        self::$_flashstore[$var] = $value;
    }

    /**
     * Clears the Flash service.
     *
     * Data that previous pages stored will not be deleted, just the data that
     * this page stored itself.
     */
    public static function clear() {
        $_SESSION[self::SESSION_KEY] = array();
    }

    /**
     * Initializes the Flash service.
     *
     * This will read flash data from the $_SESSION variable and load it into
     * the $this->previous array.
     */
    public static function init() {
        // Get flash data...
        if ( ! empty($_SESSION[self::SESSION_KEY]) && is_array($_SESSION[self::SESSION_KEY])) {
            self::$_flashstore = $_SESSION[self::SESSION_KEY];
        }
        $_SESSION[self::SESSION_KEY] = array();
    }

} // end Flash class