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
        $this->projectRoot = realpath(dirname($composer->getConfig()->get('vendor-dir')));
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

        $defaultSettings = array(
            'copy-paths' => array()
        );

        $extraConfig = $this->package->getExtra();

        if (!isset($extraConfig['wordpress'])) {
            $extraConfig['wordpress'] = array();
        }

        $settings = array_merge($defaultSettings, $extraConfig['wordpress']);

        foreach ($settings['copy-paths'] as $path) {
            if (is_string($path)) {
                $this->copyFrom($webroot, $path);
            } elseif (is_array($path) && count($path) === 2 && is_string($path[0]) && is_string($path[1])) {
                $this->copyFrom($webroot, $path[0], $path[1]);
            } else {
                throw new \RuntimeException('Unrecognised path format. Must be source path or array of source and destination paths');
            }
        }
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
     * Copy from specified file/folder
     *
     * @param string $webroot     Webroot
     * @param string $path        Path
     * @param string $destination Destination (optional)
     *
     * @return $this
     */
    private function copyFrom($webroot, $path, $destination = null)
    {
        if (null === $destination) {
            $destination = $path;
        }

        $from = $this->projectRoot . DIRECTORY_SEPARATOR . $path;
        $to = $this->projectRoot . DIRECTORY_SEPARATOR . $webroot . DIRECTORY_SEPARATOR . $destination;

        $filesystem = $this->getFilesystem();

        if ($filesystem->exists($to)) {
            $filesystem->remove($to);
        }

        $filesystem->symlink($from, $to, true);

        if ($this->io->isVerbose()) {
            $this->io->write(sprintf('Moved %s to %s', $from, $to));
        }
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
}
