<?php
namespace Phillipsdata\LinkedIn;

class LinkedInOAuthResponse
{
    private $status;
    private $raw;
    private $response;
    private $errors;

    /**
     * LinkedInResponse constructor.
     *
     * @param string $apiResponse
     */
    public function __construct($apiResponse)
    {
        $this->raw = $apiResponse;
        $this->response =  json_decode($apiResponse);
        $this->status = isset($this->response->error) ? 400 : 200;
        if (isset($this->response->error)) {
            $this->errors = isset($this->response->error_description)
                ? $this->response->error_description
                : 'Unknown Error';
        }
    }

    /**
     * Get the status of this response
     *
     * @return string The status of this response
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Get the raw data from this response
     *
     * @return string The raw data from this response
     */
    public function raw()
    {
        return $this->raw;
    }

    /**
     * Get the data response from this response
     *
     * @return string The data response from this response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Get any errors from this response
     *
     * @return string The errors from this response
     */
    public function errors()
    {
        return $this->errors;
    }
}
