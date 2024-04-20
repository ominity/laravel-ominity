<?php

namespace Ominity\Laravel\Views\Components;

use Illuminate\View\Component;
use Ominity\Api\Resources\Cms\Page;
use stdClass;

abstract class OminityComponent extends Component
{
    /**
     * The page context for components.
     *
     * @var Page
     */
    public static $page;

    /**
     * Component fields as an stdClass object.
     *
     * @var stdClass
     */
    public $fields;

    /**
     * Initialize the component with fields.
     *
     * @param  stdClass  $fields  Object containing component field values.
     */
    public function __construct(stdClass $fields)
    {
        $this->fields = $this->prepareFields($fields);
    }

    /**
     * Set the current page context globally for all components.
     *
     * @param  Page  $page  The page data from the API.
     */
    public static function setPage(Page $page)
    {
        self::$page = $page;
    }

    /**
     * Get a field value by key.
     *
     * @param  string  $key  The field key to retrieve.
     * @return mixed The field value, or null if not set.
     */
    public function field($key)
    {
        $value = $this->fields->$key ?? null;

        return $value instanceof Component ? $value->render() : $value;
    }

    /**
     * Prepare fields for rendering, converting nested components as needed.
     *
     * @param  stdClass  $fields  Object containing all fields for the component.
     * @return stdClass The modified fields object with nested components initialized.
     */
    protected function prepareFields(stdClass $fields)
    {
        foreach ($fields as $key => $field) {
            if (is_object($field) && isset($field->component)) {
                $componentClass = config('ominity.components.'.$field->component);
                if ($componentClass && class_exists($componentClass)) {
                    $fields->$key = new $componentClass($field->fields ?? new stdClass());
                }
            }
        }

        return $fields;
    }
}
