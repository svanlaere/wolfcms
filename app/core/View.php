<?php

/**
 * The View class is used to generate output based on a template.
 *
 * The class takes a template file after which you can assign properties to the
 * template. These properties become available as local variables in the
 * template.
 *
 * You can then call the display() method to get the output of the template,
 * or just call print on the template directly thanks to PHP 5's __toString()
 * magic method.
 *
 * Usage example:
 *
 * <code>
 * echo new View('my_template',array(
 *               'title' => 'My Title',
 *               'body' => 'My body content'
 *              ));
 * </code>
 *
 * Template file example (in this case my_template.php):
 *
 * <code>
 * <html>
 * <head>
 *   <title><?php echo $title;?></title>
 * </head>
 * <body>
 *   <h1><?php echo $title;?></h1>
 *   <p><?php echo $body;?></p>
 * </body>
 * </html>
 * </code>
 * You can also use Helpers in the template by loading them as follows:
 *
 * <code>
 * use_helper('HelperName', 'OtherHelperName');
 * </code>
 */
class View {
    private $file;           // String of template file
    private $vars = array(); // Array of template variables

    /**
     * Constructor for the View class.
     *
     * The class constructor has one mandatory parameter ($file) which is the
     * path to a template file and one optional paramater ($vars) which allows
     * you to make local variables available in the template.
     *
     * The View class automatically adds ".php" to the $file argument.
     *
     * @param string $file  Absolute path or path relative to the templates dir.
     * @param array $vars   Array of key/value pairs to be made available in the template.
     */
    public function __construct($file, $vars=false) {
        if (strpos($file, '/') === 0 || strpos($file, ':') === 1) {
            $this->file = $file.'.php';
        }
        else {
            $this->file = APP_PATH.'/views/'.ltrim($file, '/').'.php';
        }

        if ( ! file_exists($this->file)) {
            throw new Exception("View '{$this->file}' not found!");
        }

        if ($vars !== false) {
            $this->vars = $vars;
        }
    }

    /**
     * Assigns a specific variable to the template.
     *
     * @param mixed $name   Variable name.
     * @param mixed $value  Variable value.
     */
    public function assign($name, $value=null) {
        if (is_array($name)) {
            $this->vars = array_merge($this->vars, $name);
        } else {
            $this->vars[$name] = $value;
        }
    }

    /**
     * Returns the output of a parsed template as a string.
     *
     * @return string Content of parsed template.
     */
    public function render() {
        ob_start();

        extract($this->vars, EXTR_SKIP);
        include $this->file;

        $content = ob_get_clean();
        return $content;
    }

    /**
     * Displays the rendered template in the browser.
     */
    public function display() { echo $this->render(); }

    /**
     * Returns the parsed content of a template.
     *
     * @return string Parsed content of the view.
     */
    public function __toString() { return $this->render(); }

} // end View class
