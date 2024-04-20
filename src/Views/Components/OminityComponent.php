<?php

namespace Ominity\Laravel\Views\Components;

use Illuminate\View\Component;
use Ominity\Api\Resources\Cms\Page;

abstract class OminityComponent extends Component
{
    /**
     * @var Page
     */
    public static $page;

    /**
     * @var array
     */
    public $fields;

    /**
     * @param array $fields
     */
    public function __construct($fields)
    {
        $this->fields = $this->prepareFields($fields);
    }

    /**
     * Set current page as global variable for the OminityComponent class
     * 
     * @param Page $page
     * @return void
     */
    public static function setPage(Page $page) {
        self::$page = $page;
    }

    /**
     * Get field value by it's key
     * 
     * @param string $key
     * @return string|null
     */
    public function field($key) {
        $value = $this->fields[$key] ?? null;
        return $value instanceof Component ? $value->render() : $value;
    }

    /**
     * Make field values render friendly / render nested components to HTML
     * 
     * @param array $fiedls
     * @return array
     */
    protected function prepareFields($fields)
    {
        foreach ($fields as $key => $field) {
            if (is_array($field) && isset($field['component'])) {
                $componentClass = config('ominity.components.' . $field['component']);
                if ($componentClass && class_exists($componentClass)) {
                    $fields[$key] = new $componentClass($field['fields'] ?? []);
                }
            }
        }
        return $fields;
    }
}