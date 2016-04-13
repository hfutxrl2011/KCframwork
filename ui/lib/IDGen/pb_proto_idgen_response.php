<?php
/**
 * Auto generated from idgen_response.proto at 2016-03-20 18:29:15
 */

/**
 * idgen_res_t message
 */
class IdgenResT extends \ProtobufMessage
{
    /* Field index constants */
    const ERROR_CODE = 1;
    const ERROR_INFO = 2;
    const ID = 3;

    /* @var array Field descriptors */
    protected static $fields = array(
        self::ERROR_CODE => array(
            'name' => 'error_code',
            'required' => true,
            'type' => 5,
        ),
        self::ERROR_INFO => array(
            'name' => 'error_info',
            'required' => true,
            'type' => 7,
        ),
        self::ID => array(
            'name' => 'id',
            'required' => false,
            'type' => 5,
        ),
    );

    /**
     * Constructs new message container and clears its internal state
     *
     * @return null
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Clears message values and sets default ones
     *
     * @return null
     */
    public function reset()
    {
        $this->values[self::ERROR_CODE] = null;
        $this->values[self::ERROR_INFO] = null;
        $this->values[self::ID] = null;
    }

    /**
     * Returns field descriptors
     *
     * @return array
     */
    public function fields()
    {
        return self::$fields;
    }

    /**
     * Sets value of 'error_code' property
     *
     * @param int $value Property value
     *
     * @return null
     */
    public function setErrorCode($value)
    {
        return $this->set(self::ERROR_CODE, $value);
    }

    /**
     * Returns value of 'error_code' property
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->get(self::ERROR_CODE);
    }

    /**
     * Sets value of 'error_info' property
     *
     * @param string $value Property value
     *
     * @return null
     */
    public function setErrorInfo($value)
    {
        return $this->set(self::ERROR_INFO, $value);
    }

    /**
     * Returns value of 'error_info' property
     *
     * @return string
     */
    public function getErrorInfo()
    {
        return $this->get(self::ERROR_INFO);
    }

    /**
     * Sets value of 'id' property
     *
     * @param int $value Property value
     *
     * @return null
     */
    public function setId($value)
    {
        return $this->set(self::ID, $value);
    }

    /**
     * Returns value of 'id' property
     *
     * @return int
     */
    public function getId()
    {
        return $this->get(self::ID);
    }
}
