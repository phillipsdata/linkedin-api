<?php
namespace Phillipsdata\LinkedIn;

class LinkedInAPIResponse extends LinkedInResponse
{
    /**
     * Sets any errors that where returned in the LinkedIn response
     */
    protected function setErrors()
    {
        // Get the status if given, then record errors
        if (isset($this->response->status)) {
            $this->status = $this->response->status;
        }

        if (isset($this->response->serviceErrorCode)) {
            $this->errors = isset($this->response->message) ? $this->response->message : '';
        }
    }
}
