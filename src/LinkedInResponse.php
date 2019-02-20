<?php
namespace Phillipsdata\LinkedIn;

abstract class LinkedInResponse implements LinkedInResponseInterface
{
    /**
     * @var array The headers returned with this response
     */
    protected $headers = [];

    /**
     * @var string The status of this response
     */
    protected $status;

    /**
     * @var string The raw data from this response
     */
    protected $raw;

    /**
     * @var array The formatted body from this response
     */
    protected $response;

    /**
     * @var string The errors from this response
     */
    protected $errors;

    /**
     * @param string $apiResponse A string containing data from the LinkedIn API in json format
     * @param int $headerLength The length of the header within the apiResponse string
     */
    public function __construct($apiResponse, $headerLength)
    {
        // Split the response into header and body
        $headers = substr($apiResponse, 0, $headerLength);
        $body = substr($apiResponse, $headerLength);

        // Record the raw response including headers
        $this->raw = $apiResponse;

        // Format the response body
        $this->response = json_decode($body);

        // Parse the header
        $headerList = explode("\n", $headers);

        // Parse the status line from the header and retrieve the status code
        $statusLine = $headerList[0];
        $statusPieces = explode(' ', $statusLine, 3);
        if (count($statusPieces) == 3) {
            $this->status = $statusPieces[1];
        } else {
            // If no status line is given, set the status code to error 400 bad request
            $this->status = 400;
        }
        unset($headerList[0]);

        // Parse the rest of the headers and record them as an array
        foreach ($headerList as $header) {
            $pieces = explode(':', $header, 2);
            if (count($pieces) < 2) {
                continue;
            }

            $this->headers[$pieces[0]] = trim($pieces[1]);
        }

        $this->setErrors();
    }

    /**
     * Sets any errors that where returned in the LinkedIn response
     */
    abstract protected function setErrors();

    /**
     * Get the headers from this response
     *
     * @return array The headers returned with this response
     */
    public function headers()
    {
        return $this->headers;
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
     * Get the formatted body from this response
     *
     * @return stdClass The formatted body from this response
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
