<?php
    /*
 * Wolf CMS - Content Management Simplified. <http://www.wolfcms.org>
 * Copyright (C) 2009-2013 Martijn van der Kleijn <martijn.niji@gmail.com>
 *
 * This file is part of Wolf CMS. Wolf CMS is licensed under the GNU GPLv3 license.
 * Please see license.txt for the full license text.
 */

    /**
     * @package Installer
     */

    // Make sure we hide ugly errrors
    error_reporting(error_level: 0);

    define(constant_name: 'INSTALL_SEQUENCE', value: true);

    define(constant_name: 'CORE_ROOT', value: dirname(path: __DIR__));
    define(constant_name: 'CFG_FILE', value: '../../config.php');
    define(constant_name: 'PUBLIC_ROOT', value: '../../public/');
    define(constant_name: 'DEFAULT_ADMIN_USER', value: 'admin');
    require_once CORE_ROOT . '/Framework.php';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <title>Wolf CMS - Install/Upgrade routine</title>
    <link href="install.css" media="screen" rel="Stylesheet" type="text/css" />
</head>
<body>
    <div id="header">
        <div id="site-title">Wolf CMS</div>
    </div>
    <div id="content">

<?php
    // PHP 8.3 Refactor

    // Check if config file exists and has content
    function isConfigFileValid(): bool
    {
        return file_exists(CFG_FILE) && filesize(CFG_FILE) > 1;
    }

    // Using match expression for clearer intent
    $action = match (true) {
        isset($_POST['install']) && ! isset($_POST['commit']) && ! isConfigFileValid() => 'install',
        isset($_POST['install'], $_POST['commit'], $_POST['config']) => 'do_install',
        isset($_POST['upgrade'], $_POST['commit']) && isConfigFileValid() => 'do_upgrade',
        ! isset($_POST['upgrade'], $_POST['commit']) && isConfigFileValid() => 'upgrade',
        default => 'requirements',
    };

    switch ($action) {
        case 'install':
            require_once 'install.php';
            break;

        case 'do_install':
            $config = $_POST['config'];
            require_once 'do-install.php';
            require_once 'post-install.php';
            break;

        case 'do_upgrade':
            require_once CFG_FILE;
            require_once CORE_ROOT . '/Framework.php';
            require_once 'do-upgrade.php';
            break;

        case 'upgrade':
            require_once 'upgrade.php';
            break;

        default:
            require_once 'requirements.php';
            break;
    }
?>

    </div>
    <div id="footer">
        <p>Powered by <a href="http://www.wolfcms.org/">Wolf CMS</a></p>
    </div>
</body>
</html>