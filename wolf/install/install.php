<?php

declare(strict_types=1);

/*
 * Wolf CMS - Content Management Simplified. <http://www.wolfcms.org>
 * Copyright (C) 2009 Martijn van der Kleijn <martijn.niji@gmail.com>
 *
 * This file is part of Wolf CMS. Wolf CMS is licensed under the GNU GPLv3 license.
 * Please see license.txt for the full license text.
 */

/**
 * @package Installer
 */

/* Make sure we've been called using index.php */
if (!defined(constant_name: 'INSTALL_SEQUENCE')) {
    die('<p>Illegal call. Terminating.</p>');
}

$drivers = PDO::getAvailableDrivers();

/**
 * Render the database driver options for the select input.
 *
 * @param array $availableDrivers List of available PDO drivers.
 * @param string|null $selectedDriver The currently selected driver (if any).
 * @return void
 */
function renderDbDriverOptions(array $availableDrivers, ?string $selectedDriver = null): void
{
    $validTypes = [
        'sqlite' => 'SQLite 3',
        'mysql' => 'MySQL',
        'pgsql' => 'PostgreSQL',
    ];

    if ($selectedDriver !== null && isset($validTypes[$selectedDriver])) {
        // Selected driver option
        echo '<option value="' . htmlspecialchars(string: $selectedDriver) . '" selected>'
            . htmlspecialchars(string: $validTypes[$selectedDriver])
            . '</option>';
    } else {
        // List all available drivers in preferred order
        foreach (['mysql', 'pgsql', 'sqlite'] as $driver) {
            if (in_array(needle: $driver, haystack: $availableDrivers)) {
                echo '<option value="' . htmlspecialchars(string: $driver) . '">'
                    . htmlspecialchars(string: $validTypes[$driver])
                    . '</option>';
            }
        }
    }
}

?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const driverSelect = document.getElementById('config_db_driver');
        const dbNameInput = document.getElementById('config_db_name');
        const helpDbName = document.getElementById('help-db-name');
        const helpDbPrefix = document.getElementById('help-db-prefix');
        const tablePrefixLabel = document.querySelector('#row-table-prefix label');
        const tablePrefixRow = document.getElementById('row-table-prefix');
        const dbSocketRow = document.getElementById('row-db-socket');
        const dbHostRow = document.getElementById('row-db-host');
        const dbPortRow = document.getElementById('row-db-port');
        const dbUserRow = document.getElementById('row-db-user');
        const dbPassRow = document.getElementById('row-db-pass');
        const tablePrefixInput = document.getElementById('config_table_prefix');
        const dbPortInput = document.getElementById('config_db_port');

        // PHP-generated SQLite path (ensure proper escaping on PHP side)
        const sqlitePath = <?php echo json_encode(value: str_replace(search: "\\", replace: "/", subject: realpath(path: dirname(path: __FILE__) . "/../../../")) . "/.db.sq3"); ?>;
        
        // Helper functions to show/hide elements
        const show = (...elements) => elements.forEach(el => el?.classList.remove('hidden'));
        const hide = (...elements) => elements.forEach(el => el?.classList.add('hidden'));

        // Add CSS class `.hidden { display: none !important; }` to your CSS for this to work
        const onDriverChange = () => {
            const value = driverSelect.value;

            if (value === 'sqlite') {
                dbNameInput.value = sqlitePath;
                helpDbName.innerHTML = `
        Required. Enter the <strong>absolute</strong> path to the database file.<br/>
        You are <strong>strongly</strong> advised to keep the Wolf CMS SQLite database outside of the webserver root.
      `;
                helpDbPrefix.textContent = 'Optional. Useful to prevent conflicts if you have, or plan to have, multiple Wolf installations with a single database.';
                tablePrefixLabel?.classList.add('optional');
                tablePrefixInput.value = '';

                hide(tablePrefixRow, dbSocketRow, dbHostRow, dbPortRow, dbUserRow, dbPassRow);
            } else {
                dbNameInput.value = 'wolf';
                helpDbName.textContent = 'Required. You have to create a database manually and enter its name here.';
                show(tablePrefixRow, dbSocketRow, dbHostRow, dbPortRow, dbUserRow, dbPassRow);

                if (value === 'mysql') {
                    dbPortInput.value = '3306';
                    tablePrefixLabel?.classList.add('optional');
                    helpDbPrefix.textContent = 'Optional. Useful to prevent conflicts if you have, or plan to have, multiple Wolf installations with a single database.';
                } else if (value === 'pgsql') {
                    dbPortInput.value = '5432';
                    tablePrefixLabel?.classList.remove('optional');
                    tablePrefixInput.value = 'wolf_';
                    helpDbPrefix.innerHTML = '<strong>Required.</strong> When using PostgreSQL, you have to specify a table prefix.';
                }
            }
        };

        driverSelect.addEventListener('change', onDriverChange);
        onDriverChange();
    });

    const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    document.getElementById('timezone-input').value = timeZone;
    console.log("Detected time zone:", timeZone);

    (document.querySelector('#timezone-input') || {}).value ||= Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
</script>

<input type="hidden" id="timezone-input" name="timezone">

<h1>Installation information <img src="install-logo.png" alt="Wolf CMS logo" class="logo"></h1>
<p>
    When setting up Wolf CMS for use with multiple sites, please remember to either choose a site specific
    database name or to use a site specific table prefix.
</p>

<form action="index.php" method="post">
    <input type="hidden" name="install" value="1">
    <table class="fieldset">
        <tr>
            <td colspan="3">
                <h3>Database information</h3>
            </td>
        </tr>
        <tr>
            <td class="label"><label for="config_db_driver">Database driver</label></td>
            <td class="field">

                <select id="config_db_driver" name="config[db_driver]">
                    <?php renderDbDriverOptions($drivers, $_POST['dbtype'] ?? null); ?>
                </select>
            </td>
            <td class="help">Required.</td>
        </tr>
        <tr id="row-db-host">
            <td class="label"><label for="config_db_host">Database server</label></td>
            <td class="field"><input class="textbox" id="config_db_host" maxlength="100" name="config[db_host]"
                    size="50" type="text" value="localhost"></td>
            <td class="help">Required.</td>
        </tr>
        <tr id="row-db-port">
            <td class="label"><label class="optional" for="config_db_port">Port</label></td>
            <td class="field"><input class="textbox" id="config_db_port" maxlength="10" name="config[db_port]" size="50"
                    type="text" value=""></td>
            <td class="help">Optional. Default MySQL: 3306; default PostgreSQL: 5432</td>
        </tr>
        <tr id="row-db-socket">
            <td class="label"><label for="config_db_socket">Database unix socket</label></td>
            <td class="field"><input class="textbox" id="config_db_socket" maxlength="100" name="config[db_socket]"
                    size="50" type="text" value=""></td>
            <td class="help">Optional. When filled, database servername and port are ignored. (/path/to/socket)</td>
        </tr>
        <tr id="row-db-user">
            <td class="label"><label for="config_db_user">Database user</label></td>
            <td class="field"><input class="textbox" id="config_db_user" maxlength="255" name="config[db_user]"
                    size="50" type="text" value="root"></td>
            <td class="help">Required.</td>
        </tr>
        <tr id="row-db-pass">
            <td class="label"><label class="optional" for="config_db_pass">Database password</label></td>
            <td class="field"><input class="textbox" id="config_db_pass" maxlength="40" name="config[db_pass]" size="50"
                    type="password" value=""></td>
            <td class="help">Optional. If there is no database password, leave it blank.</td>
        </tr>
        <tr id="row-db-name">
            <td class="label"><label for="config_db_name">Database name</label></td>
            <td class="field"><input class="textbox" id="config_db_name" maxlength="120" name="config[db_name]"
                    size="50" type="text" value="wolf"></td>
            <td class="help" id="help-db-name">Required. You have to create a database manually and enter its name here.
            </td>
        </tr>
        <tr id="row-table-prefix">
            <td class="label"><label class="optional" for="config_table_prefix">Table prefix</label></td>
            <td class="field"><input class="textbox" id="config_table_prefix" maxlength="40" name="config[table_prefix]"
                    size="50" type="text" value=""></td>
            <td class="help" id="help-db-prefix">Optional. Useful to prevent conflicts if you have, or plan to have,
                multiple Wolf installations with a single database.</td>
        </tr>
        <tr>
            <td colspan="3">
                <h3>Other information</h3>
            </td>
        </tr>
        <tr>
            <td class="label"><label for="config_admin_username">Administrator username</label></td>
            <td class="field"><input class="textbox" id="config_admin_username" maxlength="40"
                    name="config[admin_username]" size="50" type="text" value="<?php echo DEFAULT_ADMIN_USER; ?>"></td>
            <td class="help">Required. Allows you to specify a custom username for the administrator. Default: admin
            </td>
        </tr>
        <tr>
            <td class="label"><label class="optional" for="config_url_suffix">URL suffix</label></td>
            <td class="field"><input class="textbox" id="config_url_suffix" maxlength="40" name="config[url_suffix]"
                    size="50" type="text" value=".html"></td>
            <td class="help">Optional. Add a suffix to simulate static html files.</td>
        </tr>
        <tr>
            <td class="label"><label class="optional" for="config_mod_rewrite">Use clean URLs</label></td>
            <td class="field"><input class="checkbox" id="config_mod_rewrite" name="config[mod_rewrite]" type="checkbox"
                    <?php echo (isset($_GET['rewrite']) && $_GET['rewrite'] == 1) ? ' checked="checked"' : ' disabled="disabled"'; ?>></td>
            <td class="help">Optional. Use clean URLs without the question mark.</td>
        </tr>
    </table>
    <p class="buttons">
        <button class="button" name="commit" type="submit">Install now!</button>
    </p>
</form>