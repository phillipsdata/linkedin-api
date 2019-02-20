<?php
namespace Phillipsdata\LinkedIn;

interface LinkedInResponseInterface
{
    /**
     * @param string $apiResponse A string containing data from the LinkedIn API in json format
     * @param int $headerLength The length of the header within the apiResponse string
     */
    public function __construct($apiResponse, $headerLength);

    /**
     * Get the headers from this response
     *
     * @return array The headers returned with this response
     */
    public function headers();

    /**
     * Get the status of this response
     *
     * @return string The status of this response
     */
    public function status();

    /**
     * Get the raw data from this response
     *
     * @return string The raw data from this response
     */
    public function raw();

    /**
     * Get the formatted body from this response
     *
     * @return stdClass The formatted body from this response
     */
    public function response();

    /**
     * Get any errors from this response
     *
     * @return string The errors from this response
     */
    public function errors();
}
