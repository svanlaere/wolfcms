<?php

// ----------------------------------------------------------------
//   global function
// ----------------------------------------------------------------

/**
 * Loads all functions from a speficied helper file.
 *
 * Example:
 * <code>
 *      use_helper('Cookie');
 *      use_helper('Number', 'Javascript', 'Cookie', ...);
 * </code>
 *
 * @param  string One or more helpers in CamelCase format.
 */
function use_helper() {
    static $_helpers = array();

    $helpers = func_get_args();

    foreach ($helpers as $helper) {
        if (in_array($helper, $_helpers)) continue;

        $helper_file = HELPER_PATH.DIRECTORY_SEPARATOR.$helper.'.php';

        if ( ! file_exists($helper_file)) {
            throw new Exception("Helper file '{$helper}' not found!");
        }

        include $helper_file;
        $_helpers[] = $helper;
    }
}

/**
 * Loads a model class from the model's file.
 *
 * Note: this is faster than waiting for the __autoload function and can be used
 *       for speed improvements.
 *
 * Example:
 * <code>
 *      use_model('Blog');
 *      use_model('Post', 'Category', 'Tag', ...);
 * </code>
 *
 * @param  string One or more Models in CamelCase format.
 */
function use_model() {
    static $_models = array();

    $models = func_get_args();

    foreach ($models as $model) {
        if (in_array($model, $_models)) continue;

        $model_file = APP_PATH.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$model.'.php';

        if ( ! file_exists($model_file)) {
            throw new Exception("Model file '{$model}' not found!");
        }

        include $model_file;
        $_models[] = $model;
    }
}


/**
 * Creates a url.
 *
 * Example output: http://www.example.com/controller/action/params#anchor
 *
 * You can add as many parameters as you want. If a param starts with # it is
 * considered to be an anchor.
 *
 * Example:
 * <code>
 *      get_url('controller/action/param1/param2');
 *      get_url('controller', 'action', 'param1', 'param2');
 * </code>
 *
 * @param string    controller, action, param and/or #anchor
 * @return string   A generated URL
 */
function get_url() {
    $params = func_get_args();
    if (count($params) === 1) return BASE_URL . $params[0];

    $url = '';
    foreach ($params as $param) {
        if (strlen((string)$param)) {
            $param_str = (string)$param;
            $url .= $param_str[0] == '#' ? $param_str : '/'. $param_str;
        }
    }
    return BASE_URL . preg_replace('/^\/(.*)$/', '$1', $url);
}

/**
 * Retrieves the request method used to access this page.
 *
 * @return string Possible values: GET, POST or AJAX
 */
function get_request_method() {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
        return 'AJAX';
    else
        return $_SERVER['REQUEST_METHOD'];
}

/**
 * Redirects this page to a specified URL.
 *
 * @param string $url
 */
function redirect($url) {
    Flash::set('HTTP_REFERER', html_encode($_SERVER['REQUEST_URI']));
    header('Location: '.$url); exit;
}

/**
 * An alias for redirect()
 *
 * @deprecated
 * @see redirect()
 */
function redirect_to($url) {
    redirect($url);
}

/**
 * Encodes HTML safely in UTF-8 format.
 *
 * You should use this instead of htmlentities.
 *
 * @param string $string    HTML to encode.
 * @return string           Encoded HTML
 */
function html_encode($string) {
    return htmlentities($string, ENT_QUOTES, 'UTF-8') ;
}

/**
 * Decodes HTML safely in UTF-8 format.
 *
 * You should use this instead of html_entity_decode.
 *
 * @param string $string    String to decode.
 * @return string           Decoded HTML
 */
function html_decode($string) {
    return html_entity_decode($string, ENT_QUOTES, 'UTF-8') ;
}

/**
 * Experimental anti XSS function.
 *
 * @todo Improve or remove.
 *
 * @param <type> $string
 * @return <type>
 */
function remove_xss($string) {
// Remove all non-printable characters. LF(0a) and CR(0d) and TAB(9) are allowed
// This prevents some character re-spacing such as <java\0script>
// Note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
    $string = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $string);

    // Straight replacements, the user should never need these since they're normal characters
    // This prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
    $search = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()~`";:?+/={}[]-_|\'\\';
    $search_count = count($search);
    for ($i = 0; $i < $search_count; $i++) {
    // ;? matches the ;, which is optional
    // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars
    // &#x0040 @ search for the hex values
        $string = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $string); // with a ;
        // &#00064 @ 0{0,7} matches '0' zero to seven times
        $string = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $string); // with a ;
    }

    // Now the only remaining whitespace attacks are \t, \n, and \r
    $ra = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'style',
        'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound',
        'title', 'link',
        'base',
        'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy',
        'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint',
        'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick',
        'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged',
        'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter',
        'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate',
        'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown',
        'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown',
        'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup',
        'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange',
        'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter',
        'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange',
        'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
    $ra_count = count($ra);

    $found = true; // Keep replacing as long as the previous round replaced something
    while ($found == true) {
        $string_before = $string;
        for ($i = 0; $i < $ra_count; $i++) {
            $pattern = '/';
            for ($j = 0; $j < strlen($ra[$i]); $j++) {
                if ($j > 0) {
                    $pattern .= '((&#[xX]0{0,8}([9ad]);)||(&#0{0,8}([9|10|13]);))*';
                }
                $pattern .= $ra[$i][$j];
            }
            $pattern .= '/i';
            $replacement = '';//substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
            $string = preg_replace($pattern, $replacement, $string); // filter out the hex tags
            if ($string_before == $string) {
            // no replacements were made, so exit the loop
                $found = false;
            }
        }
    }
    return $string;
} // remove_xss

/**
 * Prevent some basic XSS attacks, filters arrays.
 *
 * Experimental.
 *
 * @param <type> $ar
 * @return <type>
 */
function cleanArrayXSS($ar) {
    $ret = array();

    foreach ($ar as $k => $v) {
        if (is_array($k)) $k = cleanArrayXSS($k);
        else $k = remove_xss($k);

        if (is_array($v)) $v = cleanArrayXSS($v);
        else $v = remove_xss($v);

        $ret[$k] = $v;
    }

    return $ret;
}

/**
 * Prevent some basic XSS attacks
 */
function cleanXSS() {
    $in = array(&$_GET, &$_COOKIE, &$_SERVER); //, &$_POST);

    while (list($k,$v) = each($in)) {
        foreach ($v as $key => $val) {
            $oldkey = $key;

            if (!is_array($val)) {
                $val = remove_xss($val);
            }
            else {
                $val = cleanArrayXSS($val);
            }

            if (!is_array($key)) {
                $key = remove_xss($key);
            }
            else {
                $key = cleanArrayXSS($key);
            }

            unset($in[$k][$oldkey]);
            $in[$k][$key] = $val; continue;
            $in[] =& $in[$k][$key];
        }
    }
    unset($in);
    return;
}

// Clean XSS attempts using different contexts
// defaults to html type
function xssClean($data, $type = 'html') {
	// === html ===
	if ($type == "html") {
		$bad  = array("<",    ">");
		$good = array("&lt;", "&gt;");
	}
	// === style ===
	if ($type == "style") {
		$bad  = array("<",    ">",    "\"",     "'",      "``",      "(",      ")",      "&",     "\\\\");
		$good = array("&lt;", "&gt;", "&quot;", "&apos;", "&grave;", "&lpar;", "&rpar;", "&amp;", "&bsol;");
	}
	// === attribute ===
	if ($type == "attribute") {
		$bad  = array("\"",     "'",      "``");
		$good = array("&quot;", "&apos;", "&grave;");
	}
	// === script ===
	if ($type == "script") {
		$bad  = array("<",    ">",    "\"",     "'",      "\\\\",   "%",        "&");
		$good = array("&lt;", "&gt;", "&quot;", "&apos;", "&bsol;", "&percnt;", "&amp;");
	}
	// === url ===
	if ($type == "url") {
		if(preg_match("#^(?:(?:https?|ftp):{1})\/\/[^\"\s\\\\]*.[^\"\s\\\\]*$#iu",(string)$data,$match)) {
			return $match[0];
		} else {
			return 'javascript:void(0)';
		}
	}

	return stripslashes(str_replace($bad, $good, $data));
}

/**
 * Escapes special characters in Javascript strings.
 *
 * @param $value string The unescaped string.
 * @return string
 */
function jsEscape($value) {
    return strtr((string) $value, array(
        "'"     => '\\\'',
        '"'     => '\"',
        '\\'    => '\\\\',
        "\n"    => '\n',
        "\r"    => '\r',
        "\t"    => '\t',
        chr(12) => '\f',
        chr(11) => '\v',
        chr(8)  => '\b',
        '</'    => '\u003c\u002F',
    ));
}


/**
 * Displays a "404 - page not found" message and exits.
 */
function pageNotFound($url=null) {
    Observer::notify('page_not_found', $url);

    header("HTTP/1.0 404 Not Found");
    echo new View('404');
    exit;
}

/**
 * @deprecated
 * @see pageNotFound()
 */
function page_not_found($url=null) {
    pageNotFound($url);
}


/**
 * Converts a disk- or filesize number into a human readable format.
 *
 * Example: "1024" become "1 kb"
 *
 * @param int $num      The number to represent.
 * @return string       Human readable representation of the disk/filesize.
 */
function convert_size($num) {
    if ($num >= 1073741824) $num = round($num / 1073741824 * 100) / 100 .' gb';
    else if ($num >= 1048576) $num = round($num / 1048576 * 100) / 100 .' mb';
        else if ($num >= 1024) $num = round($num / 1024 * 100) / 100 .' kb';
            else $num .= ' b';
    return $num;
}


// Information about time and memory

/**
 * @todo Finish doc
 *
 * @return <type>
 */
function memory_usage() {
    return convert_size(memory_get_usage());
}

/**
 * @todo Finish doc
 *
 * @return <type>
 */
function execution_time() {
    return sprintf("%01.4f", get_microtime() - FRAMEWORK_STARTING_MICROTIME);
}

/**
 * @todo Finish doc
 *
 * @return <type>
 */
function get_microtime() {
    $time = explode(' ', microtime());
    return doubleval($time[0]) + $time[1];
}

/**
 * @todo Finish doc
 *
 * @return <type>
 */
function odd_even() {
    static $odd = true;
    return ($odd = !$odd) ? 'even': 'odd';
}

/**
 * Alias for odd_even().
 */
function even_odd() {
    return odd_even();
}

/**
 * Retrieves content from a URL by any means possible.
 *
 * Intended to retrieve content from a URL by any means. Uses file_get_contents
 * by default if possible for speed reasons. Otherwise it attempts to use CURL.
 *
 * @param string $url       URL to retrieve content from.
 * @param int $flags        Optional flags to be passed onto file_get_contents.
 * @param resource $context A context resource to be passed to file_get_contents. Optional.
 * @return mixed            Either the URL's contents as string or FALSE on failure.
 */
function getContentFromUrl($url, $flags=0, $context=false) {

    if (!defined('CHECK_TIMEOUT')) define('CHECK_TIMEOUT', 5);

    // Use file_get_contents when possible... is faster.
    if (ini_get('allow_url_fopen') && function_exists('file_get_contents')) {
        if ($context === false) $context = stream_context_create(array('http' => array('timeout' => CHECK_TIMEOUT)));

        return file_get_contents($url, $flags, $context);
    }
    else if (function_exists('curl_version')) {
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_HEADER, false);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, CHECK_TIMEOUT);
        curl_setopt ($ch, CURLOPT_TIMEOUT, CHECK_TIMEOUT);
        ob_start();
        curl_exec ($ch);
        curl_close ($ch);
        return ob_get_clean();
    }

    // If neither file_get_contents nor CURL are availabe, return FALSE.
    return false;
}

/**
 * Provides a nice print out of the stack trace when an exception is thrown.
 *
 * @param Exception $e Exception object.
 */
function framework_exception_handler($e) {
    if (!DEBUG) pageNotFound();

    echo '<style>h1,h2,h3,p,td {font-family:Verdana; font-weight:lighter;}</style>';
    echo '<h1>Wolf CMS - Uncaught '.get_class($e).'</h1>';
    echo '<h2>Description</h2>';
    echo '<p>'.$e->getMessage().'</p>';
    echo '<h2>Location</h2>';
    echo '<p>Exception thrown on line <code>'
    . $e->getLine() . '</code> in <code>'
    . $e->getFile() . '</code></p>';

    echo '<h2>Stack trace</h2>';
    $traces = $e->getTrace();
    if (count($traces) > 1) {
        echo '<pre style="font-family:Verdana; line-height: 20px">';

        $level = 0;
        foreach (array_reverse($traces) as $trace) {
            ++$level;

            if (isset($trace['class'])) echo $trace['class'].'&rarr;';

            $args = array();
            if ( ! empty($trace['args'])) {
                foreach ($trace['args'] as $arg) {
                    if (is_null($arg)) $args[] = 'null';
                    else if (is_array($arg)) $args[] = 'array['.sizeof($arg).']';
                        else if (is_object($arg)) $args[] = get_class($arg).' Object';
                            else if (is_bool($arg)) $args[] = $arg ? 'true' : 'false';
                                else if (is_int($arg)) $args[] = $arg;
                                    else {
                                        $arg = htmlspecialchars(substr($arg, 0, 64));
                                        if (strlen($arg) >= 64) $arg .= '...';
                                        $args[] = "'". $arg ."'";
                                    }
                }
            }
            echo '<strong>'.$trace['function'].'</strong>('.implode(', ',$args).')  ';
            echo 'on line <code>'.(isset($trace['line']) ? $trace['line'] : 'unknown').'</code> ';
            echo 'in <code>'.(isset($trace['file']) ? $trace['file'] : 'unknown')."</code>\n";
            echo str_repeat("   ", $level);
        }
        echo '</pre><hr/>';
    }

    $dispatcher_status = Dispatcher::getStatus();
    $dispatcher_status['request method'] = get_request_method();
    debug_table($dispatcher_status, 'Dispatcher status');
    if ( ! empty($_GET)) debug_table($_GET, 'GET');
    if ( ! empty($_POST)) debug_table($_POST, 'POST');
    if ( ! empty($_COOKIE)) debug_table($_COOKIE, 'COOKIE');
    debug_table($_SERVER, 'SERVER');
}

/**
 * Prints an HTML table with debug information.
 *
 * @param <type> $array
 * @param <type> $label
 * @param <type> $key_label
 * @param <type> $value_label
 */
function debug_table($array, $label, $key_label='Variable', $value_label='Value') {
    echo '<table cellpadding="3" cellspacing="0" style="margin: 1em auto; border: 1px solid #000; width: 90%;">';
    echo '<thead><tr><th colspan="2" style="font-family: Verdana, Arial, sans-serif; background-color: #2a2520; color: #fff;">'.$label.'</th></tr>';
    echo '<tr><td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">'.$key_label.'</td>'.
        '<td style="border-bottom: 1px solid #000;">'.$value_label.'</td></tr></thead>';

    foreach ($array as $key => $value) {
        if (is_null($value)) $value = 'null';
        else if (is_array($value)) $value = 'array['.sizeof($value).']';
            else if (is_object($value)) $value = get_class($value).' Object';
                else if (is_bool($value)) $value = $value ? 'true' : 'false';
                    else if (is_int($value)) $value = $value;
                        else {
                            $value = htmlspecialchars(substr($value, 0, 64));
                            if (strlen($value) >= 64) $value .= ' &hellip;';
                        }
        echo '<tr><td><code>'.$key.'</code></td><td><code>'.$value.'</code></td></tr>';
    }
    echo '</table>';
}

set_exception_handler('framework_exception_handler');

/**
 * This function will strip slashes if magic quotes is enabled so
 * all input data ($_GET, $_POST, $_COOKIE) is free of slashes
 */
function fix_input_quotes() {
    $in = array(&$_GET, &$_POST, &$_COOKIE);
    while (list($k,$v) = each($in)) {
        foreach ($v as $key => $val) {
            if (!is_array($val)) {
                $in[$k][$key] = stripslashes($val); continue;
            }
            $in[] =& $in[$k][$key];
        }
    }
    unset($in);
} // fix_input_quotes

if (PHP_VERSION < 6 && get_magic_quotes_gpc()) {
    fix_input_quotes();
}
