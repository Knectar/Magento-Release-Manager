<?php
/**
 * Set global/skip_process_modules_updates to '1' in app/etc/local.xml and
 * then use this script to apply updates and refresh the config cache without
 * causing a stampede on the config cache.
 *
 * @author Colin Mollenhour
 * @link https://gist.github.com/colinmollenhour/2715268
 */
umask(0);
ini_set('memory_limit','512M');
set_time_limit(0);
if(file_exists('app/Mage.php')) require_once 'app/Mage.php';
else require_once '../../app/Mage.php';

// Init without cache so we get a fresh version
Mage::app('admin','store', array('global_ban_use_cache' => TRUE));

// Only compile if currently enabled
@include 'includes/config.php';
if (defined('COMPILER_INCLUDE_PATH')) {
    echo 'Recompiling...<br>';
    Mage::getModel('compiler/process')->run();
    echo 'Done.<br>';
}

echo 'Applying updates...<br>';
Mage_Core_Model_Resource_Setup::applyAllUpdates();
Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
echo 'Done.<br>';

// Now enable caching and save
Mage::getConfig()->getOptions()->setData('global_ban_use_cache', FALSE);
Mage::app()->baseInit(array()); // Re-init cache
Mage::getConfig()->loadModules()->loadDb()->saveCache();
echo 'Saved config cache.<br>';
