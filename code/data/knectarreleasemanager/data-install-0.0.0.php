<?php
/* @var $this Mage_Core_Model_Resource_Setup */

$this->startSetup();

$frontName = ((bool)(string)Mage::getConfig()->getNode(Mage_Adminhtml_Helper_Data::XML_PATH_USE_CUSTOM_ADMIN_PATH))
    ? (string)Mage::getConfig()->getNode(Mage_Adminhtml_Helper_Data::XML_PATH_CUSTOM_ADMIN_PATH)
    : (string)Mage::getConfig()->getNode(Mage_Adminhtml_Helper_Data::XML_PATH_ADMINHTML_ROUTER_FRONTNAME);

if ($frontName === 'admin') {
    $baseUrl = Mage::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    // eg. convert "example.com" to "ex-admin" or NULL
    $frontName = preg_filter('#^https?://[^/]*?(\w\w)[^\./]*\.\w+/.*#', '\1-admin', $baseUrl);
    if (isset($frontName)) {
        Mage::getConfig()
            ->saveConfig('admin/url/custom_path', $frontName)
            ->saveConfig('admin/url/use_custom_path', true);

        // force logout from admin because path has changed
        /* @var $adminSession Mage_Admin_Model_Session */
        $adminSession = Mage::getSingleton('admin/session');
        $adminSession->unsetAll();
        $adminSession->getCookie()->delete($adminSession->getSessionName());
    }
}

$this->endSetup();
