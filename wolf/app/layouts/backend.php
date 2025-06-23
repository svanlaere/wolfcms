<?php
/*
 * Wolf CMS - Content Management Simplified. <http://www.wolfcms.org>
 * Copyright (C) 2009-2010 Martijn van der Kleijn
 * Copyright (C) 2008 Philippe Archambault
 * Licensed under GNU GPLv3.
 */

/**
 * @package Layouts
 */

// Security measure
defined('IN_CMS') || exit();

// Check user permissions
if (!AuthUser::hasPermission('admin_view')) {
    header('Location: ' . URL_PUBLIC);
    exit();
}

$ctrl   = Dispatcher::getController();
$action = Dispatcher::getAction();
$vars   = (isset($this->vars['content_for_layout']) && is_object($this->vars['content_for_layout']) && isset($this->vars['content_for_layout']->vars))
    ? $this->vars['content_for_layout']->vars
    : [];

// Generate page title
if (empty($title)) {
    $title = ($ctrl === 'plugin')
        ? (Plugin::$controllers[$action]->label ?? 'Plugin')
        : ucfirst($ctrl) . 's';

    if (!empty($vars['action'])) {
        $title .= ' - ' . ucfirst($vars['action']);
        if ($vars['action'] === 'edit' && !empty($vars['page']?->title)) {
            $title .= ' - ' . $vars['page']->title;
        }
    }
}
?><!DOCTYPE html>
<html lang="<?php echo AuthUser::getRecord()?->language ?? 'en'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo function_exists('kses') ? kses($title . ' | ' . Setting::get('admin_title'), []) : $title . ' | ' . Setting::get('admin_title'); ?></title>
    <link rel="icon" href="<?php echo PATH_PUBLIC; ?>wolf/admin/images/favicon.ico">
    <link rel="stylesheet" href="<?php echo PATH_PUBLIC; ?>wolf/admin/stylesheets/admin.css">
    <link rel="stylesheet" href="<?php echo PATH_PUBLIC; ?>wolf/admin/themes/<?php echo Setting::get('theme'); ?>/styles.css">
    <script src="<?php echo PATH_PUBLIC; ?>wolf/admin/javascripts/jquery-1.8.3.min.js"></script>
    <script src="<?php echo PATH_PUBLIC; ?>wolf/admin/javascripts/jquery-ui-1.10.3.min.js"></script>
    <script src="<?php echo PATH_PUBLIC; ?>wolf/admin/javascripts/jquery.ui.nestedSortable.js"></script>
    <script src="<?php echo PATH_PUBLIC; ?>wolf/admin/javascripts/cp-datepicker.js"></script>
    <script src="<?php echo PATH_PUBLIC; ?>wolf/admin/javascripts/wolf.js"></script>
    <script src="<?php echo PATH_PUBLIC; ?>wolf/admin/markitup/jquery.markitup.js"></script>
    <link rel="stylesheet" href="<?php echo PATH_PUBLIC; ?>wolf/admin/markitup/skins/simple/style.css">

    <?php Observer::notify('view_backend_layout_head', CURRENT_PATH); ?>

    <?php foreach (Plugin::$plugins as $plugin_id => $plugin): ?>
        <?php if (is_file(CORE_ROOT . "/plugins/$plugin_id/$plugin_id.js")): ?>
            <script src="<?php echo PATH_PUBLIC; ?>wolf/plugins/<?php echo $plugin_id; ?>/<?php echo $plugin_id; ?>.js"></script>
        <?php endif; ?>
        <?php if (is_file(CORE_ROOT . "/plugins/$plugin_id/$plugin_id.css")): ?>
            <link rel="stylesheet" href="<?php echo PATH_PUBLIC; ?>wolf/plugins/<?php echo $plugin_id; ?>/<?php echo $plugin_id; ?>.css">
        <?php endif; ?>
    <?php endforeach; ?>

    <?php foreach (Plugin::$stylesheets as $stylesheet): ?>
        <link rel="stylesheet" href="<?php echo PATH_PUBLIC; ?>wolf/plugins/<?php echo $stylesheet; ?>">
    <?php endforeach; ?>

    <?php foreach (Plugin::$javascripts as $javascript): ?>
        <script src="<?php echo PATH_PUBLIC; ?>wolf/plugins/<?php echo $javascript; ?>"></script>
    <?php endforeach; ?>

    <script>
    $(function() {
        const messages = $('.message');
        if (messages.length) {
            (function showMessages(e) {
                const delay = Math.max(1500, Math.min(5000, e.text().length * 50));
                e.fadeIn('slow').delay(delay).fadeOut('slow', function() {
                    const next = $(this).next('.message');
                    next.length ? showMessages(next) : $(this).remove();
                });
            })(messages.first());
        }

        $('input:visible:enabled:first').focus();

        $('.filter-selector').each(function() {
            const $this = $(this);
            $this.data('oldValue', $this.val());
            if ($this.val()) {
                const elem = $('#' + $this.attr('id').replace('_filter_id', '_content'));
                $this.trigger('wolfSwitchFilterIn', [$this.val(), elem]);
            }
        }).on('change', function() {
            const $this = $(this);
            const newFilter = $this.val();
            const oldFilter = $this.data('oldValue');
            $this.data('oldValue', newFilter);
            const elem = $('#' + $this.attr('id').replace('_filter_id', '_content'));
            $this.trigger('wolfSwitchFilterOut', [oldFilter, elem]);
            $this.trigger('wolfSwitchFilterIn', [newFilter, elem]);
        });
    });
    </script>
</head>
<body id="body_<?php echo $ctrl . '_' . $action; ?>">
<div id="mask"></div>
<header id="header">
    <div id="site-title"><a href="<?php echo get_url(); ?>"><?php echo Setting::get('admin_title'); ?></a></div>
    <nav id="mainTabs">
        <ul>
            <li class="plugin<?php echo $ctrl === 'page' ? ' current' : ''; ?>"><a href="<?php echo get_url('page'); ?>"><?php echo __('Pages'); ?></a></li>
            <?php if (AuthUser::hasPermission('snippet_view')): ?>
                <li class="plugin<?php echo $ctrl === 'snippet' ? ' current' : ''; ?>"><a href="<?php echo get_url('snippet'); ?>"><?php echo __('MSG_SNIPPETS'); ?></a></li>
            <?php endif; ?>
            <?php if (AuthUser::hasPermission('layout_view')): ?>
                <li class="plugin<?php echo $ctrl === 'layout' ? ' current' : ''; ?>"><a href="<?php echo get_url('layout'); ?>"><?php echo __('Layouts'); ?></a></li>
            <?php endif; ?>

            <?php foreach (Plugin::$controllers as $plugin_name => $plugin): ?>
                <?php if ($plugin->show_tab && AuthUser::hasPermission($plugin->permissions)): ?>
                    <?php Observer::notify('view_backend_list_plugin', $plugin_name, $plugin); ?>
                    <li class="plugin<?php echo ($ctrl === 'plugin' && $action === $plugin_name) ? ' current' : ''; ?>">
                        <a href="<?php echo get_url('plugin/' . $plugin_name); ?>"><?php echo $plugin->label; ?></a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (AuthUser::hasPermission('admin_edit')): ?>
                <li class="right<?php echo $ctrl === 'setting' ? ' current' : ''; ?>">
                    <a href="<?php echo get_url('setting'); ?>"><?php echo __('Administration'); ?></a>
                </li>
            <?php endif; ?>
            <?php if (AuthUser::hasPermission('user_view')): ?>
                <li class="right<?php echo $ctrl === 'user' ? ' current' : ''; ?>">
                    <a href="<?php echo get_url('user'); ?>"><?php echo __('Users'); ?></a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<?php foreach (["error", "success", "info"] as $type): ?>
    <?php if (Flash::get($type) !== null): ?>
        <div id="<?php echo $type; ?>" class="message"><?php echo Flash::get($type); ?></div>
    <?php endif; ?>
<?php endforeach; ?>

<main id="main">
    <div id="content-wrapper">
        <div id="content">
            <?php echo $content_for_layout; ?>
        </div>
    </div>
    <?php if (!empty($sidebar)): ?>
    <aside id="sidebar-wrapper">
        <div id="sidebar">
            <?php echo $sidebar; ?>
        </div>
    </aside>
    <?php endif; ?>
</main>

<footer id="footer">
    <p>
        <?php echo __('Thank you for using'); ?> <a href="http://www.wolfcms.org/" target="_blank" rel="noopener noreferrer">Wolf CMS</a> <?php echo CMS_VERSION; ?> |
        <a href="http://forum.wolfcms.org/" target="_blank" rel="noopener noreferrer"><?php echo __('Feedback'); ?></a> |
        <a href="http://docs.wolfcms.org/" target="_blank" rel="noopener noreferrer"><?php echo __('Documentation'); ?></a>
    </p>
    <?php if (DEBUG): ?>
        <p class="stats">
            <?php echo __('Page rendered in'); ?> <?php echo execution_time(); ?> <?php echo __('seconds'); ?> |
            <?php echo __('Memory usage:'); ?> <?php echo memory_usage(); ?>
        </p>
    <?php endif; ?>
    <p id="site-links">
        <?php echo __('You are currently logged in as'); ?>
        <a href="<?php echo get_url('user/edit/' . AuthUser::getId()); ?>"><?php echo AuthUser::getRecord()?->name; ?></a>
        <span class="separator"> | </span>
        <a href="<?php echo get_url('login/logout?csrf_token=' . SecureToken::generateToken(BASE_URL . 'login/logout')); ?>">
            <?php echo __('Log Out'); ?>
        </a>
        <span class="separator"> | </span>
        <a id="site-view-link" href="<?php echo URL_PUBLIC; ?>" target="_blank" rel="noopener noreferrer"><?php echo __('View Site'); ?></a>
    </p>
</footer>
</body>
</html>