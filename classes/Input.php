<?php
namespace API;

/**
 * Input validation
 * @package API
 */
class Input {
    /**
     * Filters
     * @var array
     */
    public static $FILTERS = [
        'boolean' => [FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]],
        'integer' => [FILTER_VALIDATE_INT],
        'int' => [FILTER_VALIDATE_INT],
        'float' => [FILTER_VALIDATE_FLOAT],
        'string' => [FILTER_SANITIZE_STRING, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]],
        'str' => [FILTER_SANITIZE_STRING, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]],
        'email' => [FILTER_VALIDATE_EMAIL],
        'url' => [FILTER_VALIDATE_URL],
        'ip' => [FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE]],
        'raw' => [FILTER_UNSAFE_RAW],
        'array' => [],
        'object' => [],
    ];

    /**
     * Input constructor
     * @param object|array $items
     */
    public function __construct($items = []) {
        foreach ($items as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Get value from input
     * @param string $key
     * @param mixed $default
     * @param string|null $filter
     * @return mixed
     */
    public function get($key, $default = null, $filter = null) {
        // prettier-ignore
        return $this->filter(
			isset($this->{$key})
				? $this->{$key}
				: $default,
			$filter
		);
    }

    /**
     * Filter value
     * @param mixed $value
     * @param string|null $type
     * @param array $options
     * @return mixed
     */
    public function filter($value, $type, $options = []) {
        if (!is_null($type)) {
            if (isset(Input::$FILTERS[$type]) && isset(Input::$FILTERS[$type][0])) {
                $filter = Input::$FILTERS[$type][0];
                $options = (count($options)
                        ? $options
                        : isset(Input::$FILTERS[$type][1]))
                    ? Input::$FILTERS[$type][1]
                    : [];
            } else {
                $filter = Input::$FILTERS['raw'][0];
            }

            // type casting
            if (!(is_object($value) || is_array($value))) {
                switch ($type) {
                    case 'array':
                        return (array) $value;
                    case 'object':
                        return (object) $value;
                    case 'boolean':
                        return filter_var($value, $filter, $options);
                    case 'raw':
                        return $value;
                    case 'integer':
                    case 'int':
                        $value = filter_var(is_bool($value) ? (int) $value : $value, $filter, $options);
                        return $value === false ? null : $value;
                    default:
                        $value = filter_var($value, $filter, $options);
                        return $value === false ? null : $value;
                }
            } else {
                switch ($type) {
                    case 'raw':
                        return $value;
                    case 'array':
                        if (!is_array($value)) {
                            $value = (array) $value;
                        }
                        return $value;
                    case 'object':
                        if (!is_object($value)) {
                            $value = (object) $value;
                        }
                        return $value;
                    default:
                        return null;
                }
            }
        }
        return $value;
    }

    /**
     * Set value in input
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value) {
        $this->{$key} = $value;
    }

    /**
     * Delete value in input
     * @param string $key
     * @return void
     */
    public function delete($key) {
        unset($this->{$key});
    }
}
