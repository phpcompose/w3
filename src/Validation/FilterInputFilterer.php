<?php


namespace W3\Validation;

/**
 * Class FilterInputFilterer
 * @package W3\Validation
 */
class FilterInputFilterer
{
    protected
        $filterType,
        $options;

    /**
     * FilterInputFilterer constructor.
     * @param int $filterType
     * @param null $options
     */
    public function __construct(int $filterType, $options = null)
    {
        $this->filterType = $filterType;
        $this->options = $options;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function __invoke($value)
    {
        return filter_var($value, $this->filterType, $this->options);
    }
}