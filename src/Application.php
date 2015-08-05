<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

defined('HERBIE_DEBUG') or define('HERBIE_DEBUG', false);

/**
 * The application using Pimple as dependency injection container.
 */
class Application
{
    /**
     * @var Container
     */
    protected static $container;

    /**
     * @var Page
     */
    protected static $page;

    /**
     * @var string
     */
    protected $sitePath;

    /**
     * @var string
     */
    protected $vendorDir;

    /**
     * @param string $sitePath
     * @param string $vendorDir
     */
    public function __construct($sitePath, $vendorDir = '../vendor')
    {
        Benchmark::mark();
        $this->sitePath = realpath($sitePath);
        $this->vendorDir = realpath($vendorDir);
        $this->init();
        return $this;
    }

    /**
     * Initialize the application.
     * @param array $values
     */
    private function init(array $values = [])
    {

        $errorHandler = new ErrorHandler();
        $errorHandler->register();

        static::$container = $container = new Container($this->sitePath, $this->vendorDir, $values);

        setlocale(LC_ALL, $container['Config']->get('locale'));

        // Add custom PSR-4 plugin path to Composer autoloader
        $autoload = require($this->vendorDir . '/autoload.php');
        $autoload->addPsr4('herbie\\plugin\\', $container['Config']->get('plugins.path'));

        $container['Plugins']->init($container);

        $this->fireEvent('onPluginsInitialized', ['plugins' => $container['Plugins']]);

        $container['Twig']->init();

        $this->fireEvent('onTwigInitialized', ['twig' => $container['Twig']->environment]);
    }

    /**
     * Fire an event.
     * @param  string $eventName
     * @param  array $attributes
     * @return Event
     */
    public static function fireEvent($eventName, array $attributes = [])
    {
        $event = new Event($attributes);
        return static::$container['EventDispatcher']->dispatch($eventName, $event);
    }

    /**
     * Retrieve a registered service from DI container.
     * @param string $service
     * @return mixed
     */
    public static function getService($service)
    {
        return static::$container[$service];
    }

    /**
     * Get the loaded (current) Page from DI container. This is a shortcut to Application::getService('Page').
     * @return Page
     */
    public static function getPage()
    {
        if (null === static::$page) {
            static::$page = static::getService('Page');
        }
        return static::$page;
    }

    /**
     * @return void
     */
    public function run()
    {
        /** @var Twig $twig */
        $twig = $this->getService('Twig');

        $page = $this->getPage();

        $response = $twig->renderPage($page);

        $this->fireEvent('onOutputGenerated', ['response' => $response]);

        $response->send();

        $this->fireEvent('onOutputRendered');

        if (0 < static::$container['Config']->get('display_load_time', 0)) {
            $time = Benchmark::mark();
            echo sprintf("\n<!-- Generated by Herbie in %s seconds | www.getherbie.org -->", $time);
        }
    }

}
