<?php

/**
 * SamplePlugin
 *
 * You may rename SamplePlugin to whatever you like. The PluginManager expects the plugin folder,
 * file, namespace and class to follow the same naming convention. When renaming the SamplePlugin
 * ensure that each of the following uses your new plugin name:
 *
 * plugins/SamplePlugin                          (folder)
 * plugins/SamplePlugin/SamplePlugin.php         (file)
 * namespace RaspAP\Plugins\SamplePlugin         (namespace)
 * class SamplePlugin implements PluginInterface (class)
 *
 * @description A sample user plugin to extend RaspAP's functionality
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 *              Special thanks to GitHub user @assachs
 * @license     https://github.com/RaspAP/SamplePlugin/blob/master/LICENSE
 * @see         src/RaspAP/Plugins/PluginInterface.php
 * @see         src/RaspAP/UI/Sidebar.php
 */

namespace RaspAP\Plugins\MatrixPlugin;

use RaspAP\Plugins\PluginInterface;
use RaspAP\UI\Sidebar;

class MatrixPlugin implements PluginInterface
{

    private string $pluginPath;
    private string $pluginName;
    private string $templateMain;
    private int $onboot;
    private string $serviceStatus;

    public function __construct(string $pluginPath, string $pluginName)
    {
        $this->pluginPath = $pluginPath;
        $this->pluginName = $pluginName;
        $this->templateMain = 'main';
        $this->serviceStatus = 'up';
        $this->onboot = 0;

        try {
            if ($loaded = self::loadData()) {
                    $this->serviceStatus = $loaded->getServiceStatus();
                    $this->onboot = $loaded->getOnboot();
            }
        }
        catch (\Error $e) {

        }
    }

    /**
     * Initializes SamplePlugin and creates a custom sidebar item. This is the entry point
     * for creating a custom user plugin; the PluginManager will autoload the plugin code.
     *
     * Replace 'Sample Plugin' below with the label you wish to use in the sidebar.
     * You may specify any icon in the Font Awesome 6.6 free library for the sidebar item.
     * The priority value sets the position of the item in the sidebar (lower values = higher priority).
     * The page action is handled by the plugin's namespaced handlePageAction() method.
     *
     * @param Sidebar $sidebar an instance of the Sidebar
     * @see src/RaspAP/UI/Sidebar.php
     * @see https://fontawesome.com/icons
     */
    public function initialize(Sidebar $sidebar): void
    {

        $label = _('Matrix');
        $icon = 'fas fa-plug';
        $action = 'plugin__'.$this->getName();
        $priority = 65;

        $sidebar->addItem($label, $icon, $action, $priority);
    }

    /**
     * Handles a page action by processing inputs and rendering a plugin template.
     *
     * @param string $page the current page route
     */
    public function handlePageAction(string $page): bool
    {
        // Verify that this plugin should handle the page
        if (str_starts_with($page, "/plugin__" . $this->getName())) {

            // Instantiate a StatusMessage object
            $status = new \RaspAP\Messages\StatusMessage;

            /**
             * Examples of common plugin actions are handled here:
             * 1. saveSettings
             * 2. startSampleService
             * 3. stopSampleService
             *
             * Other page actions and custom functions may be added as needed.
             */
            if (!RASPI_MONITOR_ENABLED) {
                if (isset($_POST['saveSettings'])) {
                    if (isset($_POST['onboot'])) {
                        // Validate user data

                        $onboot = (int)trim($_POST['onboot']);

                        $error = false;

                        if (!$error) {
                            $return = $this->saveSampleSettings($status,$onboot);
                            $status->addMessage('Restarting matrix.service', 'info');
                            exec('sudo /bin/systemctl stop matrix.service', $return);
                            foreach ($return as $line) {
                                $status->addMessage($line, 'info');
                            }
                            exec('sudo /bin/systemctl start matrix.service', $return);
                            foreach ($return as $line) {
                                $status->addMessage($line, 'info');
                            }
                            if ($onboot == 1){
                                exec('sudo /bin/systemctl enable matrix.service', $return);
                                foreach ($return as $line) {
                                    $status->addMessage($line, 'info');
                                }
                            }
                            else {
                                exec('sudo /bin/systemctl disable matrix.service', $return);
                                foreach ($return as $line) {
                                    $status->addMessage($line, 'info');
                                }
                            }
                        }
                    }

                } elseif (isset($_POST['startMatrixService'])) {
                    $status->addMessage('Attempting to start matrix.service', 'info');
                    exec('sudo /bin/systemctl start matrix.service', $return);
                    foreach ($return as $line) {
                        $status->addMessage($line, 'info');
                    }

                } elseif (isset($_POST['stopMatrixService'])) {
                    $status->addMessage('Attempting to stop matrix.service', 'info');
                    exec('sudo /bin/systemctl stop matrix.service', $return);
                    foreach ($return as $line) {
                        $status->addMessage($line, 'info');
                    }
                }

                exec("ps aux | grep -v grep | grep \"matrix.py\"", $output, $return);
                $this->setServiceStatus(!empty($output) ? "up" : "down");

                exec("sudo systemctl status matrix.service", $output, $return);
                array_shift($output);
                $serviceLog = implode("\n", $output);

            }

            // Populate template data
            $__template_data = [
                'title' => _('Matrix Plugin'),
                'description' => _('A plugin to control a Matrix'),
                'author' => _('A. Sachs'),
                'uri' => 'https://github.com/assachs/raspap-plugin-matrix/',
                'icon' => 'fas fa-plug', // icon should be the same used for Sidebar
                'serviceStatus' => $this->getServiceStatus(), // plugin may optionally return a service status
                'serviceName' => 'matrix.service', // an optional service name
                'action' => 'plugin__'.$this->getName(), // expected by Plugin Manager; do not edit
                'pluginName' => $this->getName(), // required for template rendering; do not edit
                // content may be passed in template data or used directly in the parent template and/or child tabs
                'content' => '',
                // example service log output. this could be replaced with an actual status result such as:
                // exec("sudo systemctl status sample.service", $output, $return);
                'serviceLog' => $serviceLog
            ];

            // update template data from property after processing page actions

            $__template_data['onboot'] = $this->getOnboot();

            echo $this->renderTemplate($this->templateMain, compact(
                "status",
                "__template_data"
            ));
            return true;
        }
        return false;
    }

    /**
     * Renders a template from inside a plugin directory
     * @param string $templateName
     * @param array $__data
     */
    public function renderTemplate(string $templateName, array $__data = []): string
    {
        $templateFile = "{$this->pluginPath}/{$this->getName()}/templates/{$templateName}.php";

        if (!file_exists($templateFile)) {
            return "Template file {$templateFile} not found.";
        }
        if (!empty($__data)) {
            extract($__data);
        }

        ob_start();
        include $templateFile;
        return ob_get_clean();
    }


    public function saveSampleSettings($status, $onboot)
    {
        $status->addMessage('Saving Matrix API key and Match UUID', 'info');
        $this->setOnboot($onboot);
        return $status;
    }

    public function getOnboot(): int
    {
        return $this->onboot;
    }

    public function setOnboot(int $onboot): void
    {
        $this->onboot = $onboot;
        $this->persistData();
    }


    /**
     * Returns a hypothetical service status
     * @return string $status
     */
    public function getServiceStatus()
    {
        return $this->serviceStatus;
    }

    // Setter for service status
    public function setServiceStatus($status)
    {
        $this->serviceStatus = $status;
        $this->persistData();
    }

    /* An example method to persist plugin data
     *
     * This writes to the volatile /tmp directory which is cleared
     * on each system boot, so should not be considered as a robust
     * method of data persistence; it's used here for demo purposes only.
     *
     * @note Plugins should avoid use of $_SESSION vars as these are
     * super globals that may conflict with other user plugins.
     */
    public function persistData()
    {
        $serialized = serialize($this);
        file_put_contents("/var/www/html/config/plugin__{$this->getName()}.data", $serialized);
    }

    // Static method to load persisted data
    public static function loadData(): ?self
    {
        $filePath = "/var/www/html/config/plugin__".self::getName() .".data";
        if (file_exists($filePath)) {
            $data = file_get_contents($filePath);
            return unserialize($data);
        }
        return null;
    }

    // Returns an abbreviated class name
    public static function getName(): string
    {
        return basename(str_replace('\\', '/', static::class));
    }

}
