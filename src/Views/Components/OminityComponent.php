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
     * Get a field value by key. Handles both single component fields and arrays of components.
     *
     * @param  string  $key  The field key to retrieve.
     * @return mixed The field value rendered as HTML, or null if not set.
     */
    public function field($key)
    {
        $value = $this->fields->$key ?? null;

        if (is_array($value)) {
            return array_map(function ($component) {
                return $component instanceof Component ? $component->render()->render() : $component;
            }, $value);
        } else {
            return $value instanceof Component ? $value->render()->render() : $value;
        }
    }

    /**
     * Prepare fields for rendering, converting nested components as needed.
     * This method now also initializes arrays of components.
     *
     * @param  stdClass  $fields  Object containing all fields for the component.
     * @return stdClass The modified fields object with nested components initialized.
     */
    protected function prepareFields(stdClass $fields)
    {
        foreach ($fields as $key => $field) {
            if (is_object($field) && isset($field->component)) {
                // Initialize a single nested component
                $componentClass = config('ominity.pages.components.'.$field->component);
                if ($componentClass && class_exists($componentClass)) {
                    $fields->$key = new $componentClass($field->fields ?? new stdClass());
                }
            } elseif (is_array($field)) {
                // Initialize an array of nested components
                $fields->$key = array_map(function ($item) {
                    if (is_object($item) && isset($item->component)) {
                        $componentClass = config('ominity.pages.components.'.$item->component);
                        if ($componentClass && class_exists($componentClass)) {
                            return new $componentClass($item->fields ?? new stdClass());
                        }
                    }

                    return $item;
                }, $field);
            }
        }

        return $fields;
    }
}
