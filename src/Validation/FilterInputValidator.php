<?php


namespace W3\Validation;

/**
 * Class FilterInputValidator
 * @package W3\Validation
 */
class FilterInputValidator extends FilterInputFilterer
{
    public $errorMessage = null;
    protected $typeMessages = [
        FILTER_VALIDATE_BOOLEAN => "Boolean",
        FILTER_VALIDATE_DOMAIN => "Domain",
        FILTER_VALIDATE_EMAIL => "Email",
        FILTER_VALIDATE_FLOAT => "Float",
        FILTER_VALIDATE_INT => "Integer",
        FILTER_VALIDATE_IP => "IP",
        FILTER_VALIDATE_MAC => "MAC Address",
        FILTER_VALIDATE_URL => "URL",
        FILTER_SANITIZE_STRING => "String",
    ];

    /**
     * FilterInputValidator constructor.
     * @param int $filterType
     * @param null $options
     * @param string|null $errorMessage
     */
    public function __construct(int $filterType, $options = null, string $errorMessage = null)
    {
        parent::__construct($filterType, $options);
        $this->errorMessage = $errorMessage;
    }

    /**
     * @param $value
     * @return mixed|string|null
     */
    public function __invoke($value)
    {
        $filtered = parent::__invoke($value);
        if($filtered !== $value) {
            if($this->errorMessage) return $this->errorMessage;
            else {
                return "Invalid " . ($this->typeMessages[$this->filterType] ?? 'value');
            }
        }

        return null;
    }
}