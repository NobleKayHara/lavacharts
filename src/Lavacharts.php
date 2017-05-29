<?php

namespace Khill\Lavacharts;

use Khill\Lavacharts\Charts\Chart;
use Khill\Lavacharts\Charts\ChartFactory;
use Khill\Lavacharts\Dashboards\Dashboard;
use Khill\Lavacharts\Dashboards\DashboardFactory;
use Khill\Lavacharts\Dashboards\Filters\Filter;
use Khill\Lavacharts\Dashboards\Filters\FilterFactory;
use Khill\Lavacharts\Dashboards\Wrappers\ChartWrapper;
use Khill\Lavacharts\Dashboards\Wrappers\ControlWrapper;
use Khill\Lavacharts\DataTables\DataTable;
use Khill\Lavacharts\DataTables\Formats\Format;
use Khill\Lavacharts\Exceptions\InvalidLabel;
use Khill\Lavacharts\Exceptions\InvalidLavaObject;
use Khill\Lavacharts\Javascript\ScriptManager;
use Khill\Lavacharts\Support\Html\HtmlFactory;
use Khill\Lavacharts\Support\Psr4Autoloader;
use Khill\Lavacharts\Values\ElementId;
use Khill\Lavacharts\Values\Label;
use Khill\Lavacharts\Values\StringValue;
use Khill\Lavacharts\Support\Contracts\RenderableInterface as Renderable;

/**
 * Lavacharts - A PHP wrapper library for the Google Chart API
 *
 *
 * @category  Class
 * @package   Khill\Lavacharts
 * @author    Kevin Hill <kevinkhill@gmail.com>
 * @copyright (c) 2017, KHill Designs
 * @link      http://github.com/kevinkhill/lavacharts GitHub Repository Page
 * @link      http://lavacharts.com                   Official Docs Site
 * @license   http://opensource.org/licenses/MIT      MIT
 */
class Lavacharts
{
    /**
     * Lavacharts version
     */
    const VERSION = '3.1.6';

    /**
     * Default Config for Lavacharts
     *
     * @var array
     */
    private $config;

    /**
     * Holds all of the defined Charts and DataTables.
     *
     * @var \Khill\Lavacharts\Volcano
     */
    private $volcano;

    /**
     * ScriptManager for outputting lava.js and chart/dashboard javascript
     *
     * @var \Khill\Lavacharts\Javascript\ScriptManager
     */
    private $scriptManager;

    /**
     * Lavacharts constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = empty($config) ? $this->getDefaultConfig() : $config;

        if (! $this->usingComposer()) {
            require_once(__DIR__.'/Support/Psr4Autoloader.php');

            $loader = new Psr4Autoloader;
            $loader->register();
            $loader->addNamespace('Khill\Lavacharts', __DIR__);
        }

        $this->volcano       = new Volcano;
        $this->chartFactory  = new ChartFactory;
        $this->dashFactory   = new DashboardFactory;
        $this->scriptManager = new ScriptManager;
    }

    /**
     * Magic function to reduce repetitive coding and create aliases.
     *
     * @since  1.0.0
     * @param  string $method Name of method
     * @param  array  $args   Passed arguments
     * @throws \Khill\Lavacharts\Exceptions\InvalidLabel
     * @throws \Khill\Lavacharts\Exceptions\InvalidLavaObject
     * @throws \Khill\Lavacharts\Exceptions\InvalidFunctionParam
     * @return mixed Returns Charts, Formats and Filters
     */
    public function __call($method, $args)
    {
        //Charts
        if (ChartFactory::isValidChart($method)) {
            if (isset($args[0]) === false) {
                throw new InvalidLabel;
            }

            if ($this->exists($method, $args[0])) {
                $label = new Label($args[0]);

                return $this->volcano->get($method, $label);
            } else {
                $chart = $this->chartFactory->create($method, $args);

                return $this->volcano->store($chart);
            }
        }

        //Filters
        if ((bool) preg_match('/Filter$/', $method)) {
            $options = isset($args[1]) ? $args[1] : [];

            return FilterFactory::create($method, $args[0], $options);
        }

        //Formats
        if ((bool) preg_match('/Format$/', $method)) {
            $options = isset($args[0]) ? $args[0] : [];

            return Format::create($method, $options);
        }

        return null;
    }

    /**
     * Set a config value for the package.
     *
     * @since  3.1.6
     * @param  string $key
     * @param  string $value
     * @return bool True if the config value was set, false if not a valid config value
     */
    public function setConfig($key, $value)
    {
        if (array_key_exists($key, $this->config)) {
            $this->config[$key] = $value;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieve a config value from the package.
     *
     * @since  3.1.6
     * @param  string $key
     * @return string
     */
    public function getConfig($key)
    {
        return array_key_exists($key, $this->config) ? $this->config[$key] : null;
    }

    /**
     * Create a new DataTable using the DataFactory
     *
     * If the additional DataTablePlus package is available, then one will
     * be created, otherwise a standard DataTable is returned.
     *
     * @since  3.0.3
     * @uses   \Khill\Lavacharts\DataTables\DataFactory
     * @return \Khill\Lavacharts\DataTables\DataTable
     */
    public function DataTable()
    {
        $dataFactory = __NAMESPACE__.'\\DataTables\\DataFactory::DataTable';

        return call_user_func_array($dataFactory, func_get_args());
    }

    /**
     * Create a new Dashboard
     *
     * @since  3.0.0
     * @param  \Khill\Lavacharts\Values\Label $label
     * @param  \Khill\Lavacharts\DataTables\DataTable $dataTable
     * @return \Khill\Lavacharts\Dashboards\Dashboard
     */
    public function Dashboard($label, DataTable $dataTable)
    {
        if ($this->exists('Dashboard', $label)) {
            return $this->volcano->get('Dashboard', $label);
        }

        return $this->volcano->store(
            $this->dashFactory->create(func_get_args())
        );
    }

    /**
     * Create a new ControlWrapper from a Filter
     *
     * @since  3.0.0
     * @uses   \Khill\Lavacharts\Values\ElementId
     * @param  \Khill\Lavacharts\Dashboards\Filters\Filter $filter Filter to wrap
     * @param  string $elementId HTML element ID to output the control.
     * @return \Khill\Lavacharts\Dashboards\Wrappers\ControlWrapper
     */
    public function ControlWrapper(Filter $filter, $elementId)
    {
        $elementId = new ElementId($elementId);

        return new ControlWrapper($filter, $elementId);
    }

    /**
     * Create a new ChartWrapper from a Chart
     *
     * @since  3.0.0
     * @uses   \Khill\Lavacharts\Values\ElementId
     * @param  \Khill\Lavacharts\Charts\Chart $chart Chart to wrap
     * @param  string $elementId HTML element ID to output the control.
     * @return \Khill\Lavacharts\Dashboards\Wrappers\ChartWrapper
     */
    public function ChartWrapper(Chart $chart, $elementId)
    {
        $elementId = new ElementId($elementId);

        return new ChartWrapper($chart, $elementId);
    }

    /**
     * Locales are used to customize text for a country or language.
     *
     * This will affect the formatting of values such as currencies, dates, and numbers.
     *
     * By default, Lavacharts is loaded with the "en" locale. You can override this default
     * by explicitly specifying a locale when creating the DataTable.
     *
     * @deprecated 3.1.6 The new method for setting the locale is: setConfig('locale', 'en')
     * @since  3.1.0
     * @param  string $locale
     * @return $this
     * @throws \Khill\Lavacharts\Exceptions\InvalidStringValue
     */
    public function setLocale($locale)
    {
        $this->config['locale'] = (string) new StringValue($locale);

        return $this;
    }
    /**
     * Returns the current locale used in the DataTable
     *
     * @deprecated 3.1.6 The new method for fetching the locale is: getConfig('locale')
     * @since  3.1.0
     * @return string
     */
    public function getLocale()
    {
        return $this->config['locale'];
    }

    /**
     * Outputs the lava.js module for manual placement.
     *
     * Will be depreciating jsapi in the future
     *
     * @since  3.0.3
     * @return string Google Chart API and lava.js script blocks
     */
    public function lavajs()
    {
        return (string) $this->scriptManager->getLavaJsModule($this->config);
    }

    /**
     * Outputs the link to the Google JSAPI
     *
     * @since      2.3.0
     * @deprecated 3.0.3
     * @return string Google Chart API and lava.js script blocks
     */
    public function jsapi()
    {
        return $this->lavajs();
    }

    /**
     * Checks to see if the given chart or dashboard exists in the volcano storage.
     *
     * @since  2.4.2
     * @uses   \Khill\Lavacharts\Values\Label
     * @param  string $type Type of object to isNonEmpty.
     * @param  string $label Label of the object to isNonEmpty.
     * @return boolean
     */
    public function exists($type, $label)
    {
        $label = new Label($label);

        if ($type == 'Dashboard') {
            return $this->volcano->checkDashboard($label);
        } else {
            return $this->volcano->checkChart($type, $label);
        }
    }

    /**
     * Fetches an existing Chart or Dashboard from the volcano storage.
     *
     * @since  3.0.0
     * @uses   \Khill\Lavacharts\Values\Label
     * @param  string $type  Type of Chart or Dashboard.
     * @param  string $label Label of the Chart or Dashboard.
     * @return \Khill\Lavacharts\Support\Contracts\RenderableInterface
     * @throws \Khill\Lavacharts\Exceptions\InvalidLavaObject
     */
    public function fetch($type, $label)
    {
        $label = new Label($label);

        if (strpos($type, 'Chart') === false && $type != 'Dashboard') {
            throw new InvalidLavaObject($type);
        }

        return $this->volcano->get($type, $label);
    }

    /**
     * Stores a existing Chart or Dashboard into the volcano storage.
     *
     * @since  3.0.0
     * @param  \Khill\Lavacharts\Support\Contracts\RenderableInterface $renderable A Chart or Dashboard.
     * @return \Khill\Lavacharts\Support\Contracts\RenderableInterface
     */
    public function store(Renderable $renderable)
    {
        return $this->volcano->store($renderable);
    }

    /**
     * Renders Charts or Dashboards into the page
     *
     * Given a type, label, and HTML element id, this will output
     * all of the necessary javascript to generate the chart or dashboard.
     *
     * As of version 3.1, the elementId parameter is optional, but only
     * if the elementId was set explicitly to the Renderable.
     *
     * @since  2.0.0
     * @uses   \Khill\Lavacharts\Values\Label
     * @uses   \Khill\Lavacharts\Values\ElementId
     * @uses   \Khill\Lavacharts\Support\Buffer
     * @param  string $type       Type of object to render.
     * @param  string $label      Label of the object to render.
     * @param  mixed  $elementId  HTML element id to render into.
     * @param  mixed  $div        Set true for div creation, or pass an array with height & width
     * @return string
     */
    public function render($type, $label, $elementId = null, $div = false)
    {
        $label = new Label($label);

        if (is_string($elementId)) {
            $elementId = new ElementId($elementId);
        }

        if (is_array($elementId)) {
            $div = $elementId;
        }

        if ($type == 'Dashboard') {
            $buffer = $this->renderDashboard($label, $elementId);
        } else {
            $buffer = $this->renderChart($type, $label, $elementId, $div);
        }

        return $buffer->getContents();
    }

    /**
     * Renders all charts and dashboards that have been defined
     *
     * @since  3.1.0
     * @return string
     */
    public function renderAll()
    {
        $output = '';

        if ($this->scriptManager->lavaJsRendered() === false) {
            $output = $this->scriptManager->getLavaJsModule();
        }

        $renderables = $this->volcano->getAll();

        foreach ($renderables as $renderable) {
            $output .= $this->scriptManager->getOutputBuffer($renderable);
        }

        return $output;
    }

    /**
     * Renders the chart into the page
     *
     * Given a chart label and an HTML element id, this will output
     * all of the necessary javascript to generate the chart.
     *
     * @since  3.0.0
     * @param  string                             $type
     * @param  \Khill\Lavacharts\Values\Label     $label
     * @param  \Khill\Lavacharts\Values\ElementId $elementId HTML element id to render the chart into.
     * @param  bool|array                         $div       Set true for div creation, or pass an array with height & width
     * @return \Khill\Lavacharts\Support\Buffer
     * @throws \Khill\Lavacharts\Exceptions\ChartNotFound
     * @throws \Khill\Lavacharts\Exceptions\InvalidConfigValue
     * @throws \Khill\Lavacharts\Exceptions\InvalidDivDimensions
     */
    private function renderChart($type, Label $label, ElementId $elementId = null, $div = false)
    {
        /** @var \Khill\Lavacharts\Charts\Chart $chart */
        $chart = $this->volcano->get($type, $label);

        if ($elementId instanceof ElementId) {
            $chart->setElementId($elementId);
        }

        $buffer = $this->scriptManager->getOutputBuffer($chart);

        if ($this->scriptManager->lavaJsRendered() === false) {
            $buffer->prepend($this->lavajs());
        }

        if ($div !== false) {
            $buffer->prepend(HtmlFactory::createDiv($chart->getElementIdStr(), $div));
        }

        return $buffer;
    }

    /**
     * Renders the chart into the page
     * Given a chart label and an HTML element id, this will output
     * all of the necessary javascript to generate the chart.
     *
     * @since  3.0.0
     * @uses   \Khill\Lavacharts\Support\Buffer   $buffer
     * @param  \Khill\Lavacharts\Values\Label     $label
     * @param  \Khill\Lavacharts\Values\ElementId $elementId HTML element id to render the chart into.
     * @return \Khill\Lavacharts\Support\Buffer
     * @throws \Khill\Lavacharts\Exceptions\DashboardNotFound
     */
    private function renderDashboard(Label $label, ElementId $elementId = null)
    {
        /** @var \Khill\Lavacharts\Dashboards\Dashboard $dashboard */
        $dashboard = $this->volcano->get('Dashboard', $label);

        if ($elementId instanceof ElementId) {
            $dashboard->setElementId($elementId);
        }

        $buffer = $this->scriptManager->getOutputBuffer($dashboard);

        if ($this->scriptManager->lavaJsRendered() === false) {
            $buffer->prepend($this->lavajs());
        }

        return $buffer;
    }

    /**
     * Checks if running in composer environment
     *
     * This will check if the folder 'composer' is within the path to Lavacharts.
     *
     * @since  2.4.0
     * @return boolean
     */
    private function usingComposer()
    {
        if (strpos(realpath(__FILE__), 'composer') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Loads the default configuration options from the defaults file.
     *
     * @since  3.1.6
     * @return array
     */
    private function getDefaultConfig()
    {
        return require(__DIR__.'/Laravel/config/lavacharts.php');
    }
}
