<?php
namespace W3\Validation;

/**
 * Class Processor
 * @package W3\Validation
 */
class Processor
{
    public $trim = null;
    public $requiredMessage = 'Required';

    protected
        /**
         * @var array
         */
        $rules = [],

        $filterers = [],

        $validators = [],

        $requiredValues = [];


    /**
     * Processor constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param string|array $names
     * @param callable $filterer
     * @return Processor
     */
    public function addFilterer($names, callable $filterer) : self
    {
        if(!is_array($names)) {
            $names = [$names];
        }

        foreach($names as $name) {
            $this->filterers[$name][] = $filterer;
        }

        return $this;
    }

    /**
     * @param string|array $names
     * @param callable $validator
     * @return Processor
     */
    public function addValidator($names, callable $validator): self
    {
        if(!is_array($names)) {
            $names = [$names];
        }

        foreach($names as $name) {
            $this->validators[$name][] = $validator;
        }

        return $this;
    }

    /**
     * @param array $names
     * @return Processor
     */
    public function setRequiredValues(array $names) : self
    {
        $this->requiredValues = $names;

        return $this;
    }

    /**
     * @param array $values
     * @return array
     */
    public function filter(array $values) : array
    {
        $results = [];
        foreach($values as $name => $value) {
            $filterers = $this->filterers[$name] ?? null;
            if($filterers) {
                foreach($filterers as $filterer) { // filters processing for the key/value
                    $value = $filterer($value);
                }
            }

            $results[$name] = $value;
        }

        return $results;
    }

    /**
     * @param array $values
     * @return array|null
     */
    public function validate(array $values) : ?array
    {
        $errors = [];
        $break = false;

        foreach($this->requiredValues as $name) {
            $value = $values[$name] ?? null;
            if(!is_array($value)) {
                $value = trim($value);
            } else {
                // handling file upload
                if(isset($value['error'])) {
                    if($value['error'] == UPLOAD_ERR_NO_FILE) {
                        $value = null;
                    }
                }
            }

            // check missing required
            if(empty($value)) {
                $errors[$name][] = $this->requiredMessage;
            }
        }

        if(!$errors && !$break) {
            foreach($values as $name => $value) {
                $validators = $this->validators[$name] ?? null;
                if($validators) {
                    foreach($validators as $validator) {
                        $error = $validator($value);
                        if($error) {
                            $errors[$name][] = $error;
                        }
                    }
                }
            }
        }

        if(empty($errors)) {
            return null;
        } else {
            return $errors;
        }
    }

    /**
     * @param array $values
     * @return array|null
     */
    public function process(array &$values) : ?array
    {
        $values = $this->filter($values);
        $errors = $this->validate($values);

        return $errors;
    }
}