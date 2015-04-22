<?php

namespace MarkWilson\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Wordpress plugin
 *
 * @package MarkWilson\Composer
 * @author  Mark Wilson <mark@89allport.co.uk>
 */
class WordpressPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Root package
     *
     * @var Package
     */
    private $package;

    /**
     * IO
     *
     * @var IOInterface
     */
    private $io;

    /**
     * Project root
     *
     * @var string
     */
    private $projectRoot;

    /**
     * Activate Wordpress plugin
     *
     * @param Composer    $composer Composer
     * @param IOInterface $io       IO
     *
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->package = $composer->getPackage();
        $this->io = $io;

        // TODO: get the project root - must be a better way to do this
        $this->projectRoot = dirname($composer->getConfig()->get('vendor-dir'));
    }

    /**
     * Get the subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_INSTALL_CMD => array(
                array('initialiseWordpress', 0)
            ),
            ScriptEvents::POST_UPDATE_CMD => array(
                array('initialiseWordpress', 0)
            ),
        );
    }

    /**
     * Initialise the project
     *
     * @param Event $event Event
     *
     * @return void
     */
    public function initialiseWordpress(Event $event)
    {
        $webroot = $this->getWebroot();
        $devMode = $this->isDevMode();

        $this->copyIndex($webroot, $devMode);
        $this->copyContent($webroot, $devMode);
        $this->copyAssets($webroot, $devMode);
    }

    /**
     * Are we in dev mode?
     *
     * @return boolean
     */
    private function isDevMode()
    {
        return $this->package->isDev();
    }

    /**
     * Get the webroot data
     *
     * @return string
     */
    private function getWebroot()
    {
        $extraData = $this->package->getExtra();

        return $extraData['webroot-dir'];
    }

    /**
     * Copy the index script
     *
     * @param string  $webroot Webroot
     * @param boolean $devMode Dev mode
     *
     * @return $this
     */
    private function copyIndex($webroot, $devMode = false)
    {
        $this->copyFrom($webroot, $devMode, 'index.php');

        return $this;
    }

    /**
     * Copy content folder
     *
     * @param string  $webroot Webroot
     * @param boolean $devMode Dev mode
     *
     * @return $this
     */
    private function copyContent($webroot, $devMode = false)
    {
        $this->copyFrom($webroot, $devMode, 'content');

        return $this;
    }

    /**
     * Copy assets folder
     *
     * @param string  $webroot Webroot
     * @param boolean $devMode Dev mode
     *
     * @return $this
     */
    private function copyAssets($webroot, $devMode = false)
    {
        $this->copyFrom($webroot, $devMode, 'assets');

        return $this;
    }

    /**
     * Get filesystem
     *
     * @return Filesystem
     */
    private function getFilesystem()
    {
        return new Filesystem();
    }

    /**
     * Copy from specified file/folder
     *
     * @param string  $webroot Webroot
     * @param boolean $devMode Dev mode
     * @param string  $path    Path
     *
     * @return $this
     */
    private function copyFrom($webroot, $devMode, $path)
    {
        $from = $this->projectRoot . DIRECTORY_SEPARATOR . $path;
        $to = $this->projectRoot . DIRECTORY_SEPARATOR . $webroot . DIRECTORY_SEPARATOR . $path;

        $filesystem = $this->getFilesystem();

        if ($filesystem->exists($to)) {
            $filesystem->remove($to);
        }

        if ($devMode) {
            $filesystem->symlink($from, $to, true);
        } else {
            if (!is_file($from)) {
                $filesystem->mirror($from, $to);
            } else {
                $filesystem->copy($from, $to);
            }
        }

        if ($this->io->isVerbose()) {
            $this->io->write(sprintf('Moved %s to %s', $from, $to));
        }
    }
}
