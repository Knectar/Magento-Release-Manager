<?php
/**
 * Safe upgrades to Magento store.
 * 
 * Visit this script to get further help then use the
 * pre- and post-deploy URLs as web hooks.
 * Upgrades will not work if Magento is not yet installed.
 *
 * @author Daniel Deady <daniel.deady@knectar.com>
 */

const CHALLENGE_REALM = 'Private deployment webhook';
const QUERY_PRE = 'pre';
const QUERY_POST = 'post';

if (! filter_var(@$_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN)) {
    header('HTTP/1.0 403 Forbidden');
    die ("Request must be <a href='https://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}'>encrypted</a>\n");
}

function check_htpasswd($filename)
{
    $user = @$_SERVER['PHP_AUTH_USER'];
    $pass = @$_SERVER['PHP_AUTH_PW'];
    // apr1 is Apache's implementation of MD5, 2y is bcrypt
    $pattern = '/^'.preg_quote($user).':(\$apr1\$(\w+)\$\w+)$/m';
    $passwds = (string) file_get_contents($filename);
    if (! preg_match($pattern, $passwds, $passwd)) return false;

    list(, $hash, $salt) = $passwd;
    $result = exec('openssl passwd -apr1 -salt '.escapeshellarg($salt).' '.escapeshellarg($pass));
    return $result === $hash;
}

if (! check_htpasswd('.htpasswd')) {
    $access = (string) file_get_contents('.htaccess');
    if (preg_match('/^\s*AuthType\s+Basic$/im', $access) &&
        preg_match('/^\s*AuthUserFile\s+(\S+)$/im', $access, $userfile) &&
        preg_match('/^\s*AuthName\s+([\'"]?)(.*)\1$/im', $access, $realm))
    {
        $userfile = $userfile[1];
        $realm = $realm[2];
        if (check_htpasswd($userfile)) goto action;
    }
    else
    {
        $realm = CHALLENGE_REALM;
    }
    header('WWW-Authenticate: Basic realm="'.addslashes($realm).'"', true, 401);
    die ("Not authorized\n");
}

// Let's GOTO like it's 1990!
action:
switch (@$_SERVER['QUERY_STRING']) {
    case QUERY_PRE:
        touch('maintenance.flag');
        if (! file_exists('maintenance.flag')) {
            header('HTTP/1.0 500 Internal Server Error');
            echo "Could not lock maintenance mode\n";
        }
        break;

    case QUERY_POST:

        /**
         * Perform upgrades
         *
         * @see https://gist.github.com/colinmollenhour/2715268
         * @author Colin Mollenhour
         */
        umask(0);
        ini_set('memory_limit','512M');
        set_time_limit(0);
        require_once 'app/Mage.php';

        // Init without cache so we get a fresh version
        Mage::app('admin','store', array('global_ban_use_cache' => TRUE));
        Mage_Core_Model_Resource_Setup::applyAllUpdates();
        Mage_Core_Model_Resource_Setup::applyAllDataUpdates();

        // Now enable caching and save
        Mage::getConfig()->getOptions()->setData('global_ban_use_cache', FALSE);
        Mage::app()->baseInit(array()); // Re-init cache
        Mage::getConfig()->loadModules()->loadDb()->saveCache();

        // Unset maintenance mode
        unlink('maintenance.flag');
        break;

    default:
        $uri = 'https://' . USER . ':' . PASS . '@';
        $uri .= $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

        header('HTTP/1.0 300 Multiple Choices');
        echo "<strong>Request either URL</strong>";
        echo "<ul>\n";
        echo "<li><pre>{$uri}?", QUERY_PRE, "</pre></li>\n";
        echo "<li><pre>{$uri}?", QUERY_POST, "</pre></li>\n";
        echo "</ul>\n";
}
