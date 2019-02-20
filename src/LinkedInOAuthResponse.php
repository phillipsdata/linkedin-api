<?php
namespace Phillipsdata\LinkedIn;

class LinkedInOAuthResponse extends LinkedInResponse
{
    /**
     * Sets any errors that where returned in the LinkedIn response
     */
    protected function setErrors()
    {
        // Get the status if given, then record errors
        if (isset($this->response->error)) {
            $this->errors = isset($this->response->error_description)
                ? $this->response->error_description
                : '';
        }
    }
}
