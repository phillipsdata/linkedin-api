<?php
namespace Phillipsdata\LinkedIn;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'linkedin_api_response.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'linkedin_oauth_response.php';

class LinkedIn
{
    /**
     * The url endpoint for the LinkedIn APIs
     *
     * @var string $apiUrl
     */
    private $oauthUrl = 'https://www.linkedin.com/oauth/v2';
    /**
     * The url endpoint for the LinkedIn APIs
     *
     * @var string $apiUrl
     */
    private $apiUrl = 'https://api.linkedin.com';
    /**
     * The LinkedIn API Key
     *
     * @var string $apiKey
     */
    private $apiKey;
    /**
     * The LinkedIn API Secret
     *
     * @var string $apiSecret
     */
    private $apiSecret;
    /**
     * The access token information for the client for which to make requests
     *
     * @var array $accessToken
     */
    private $accessToken;
    /**
     * The data sent with the last request served by this API
     *
     * @var array $lastRequest
     */
    private $lastRequest = [];
    /**
     * The uri a user is redirected to after making an authorization request
     *
     * @var string $redirectUri
     */
    private $redirectUri = '';

    /**
     * Sets credentials for all future API interactions
     *
     * @param string $apiKey The LinkedIn API Key
     * @param string $apiSecret The LinkedIn API Secret
     * @param string $redirectUri The uri a user is redirected to after making an authorization request
     */
    public function __construct($apiKey, $apiSecret, $redirectUri)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->redirectUri = $redirectUri;
        $this->accessToken = (object)['access_token' => '', 'expires_in' => ''];
    }

    /**
     * Makes an API request to LinkedIn
     *
     * @param string $action The api endpoint for the request
     * @param array $data The data to send with the request
     * @param string $method The data transfer method to use
     * @param string $oauthRequest True to send the request to the oauth endpoint, false otherwise
     * @return stdClass The data returned by the request
     */
    private function makeRequest($action, array $data, $method, $oauthRequest = false)
    {
        $url = ($oauthRequest ? $this->oauthUrl : $this->apiUrl) . '/' . $action;
        $ch = curl_init();

        switch (strtoupper($method)) {
            case 'GET':
            case 'DELETE':
                $url .= empty($data) ? '' : '?' . http_build_query($data);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
            default:
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);

        $headers = [
            'Authorization: Bearer ' . $this->accessToken->access_token,
            'Cache-Control: no-cache',
            'X-RestLi-Protocol-Version: 2.0.0',
            'x-li-format: json',
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->lastRequest = ['content' => $data, 'headers' => $headers];
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $result = json_encode((object)['error' => 'curl_error', 'error_description' => curl_error($ch)]);
        }
        curl_close($ch);

        // Return request response
        return $oauthRequest ? new LinkedInOAuthResponse($result) : new LinkedInAPIResponse($result);
    }

    /**
     * Gets the access token for this API
     *
     * @param string $code The authorization code given by user app permissions approval
     * @return string The access token
     */
    public function getAccessToken($code = null)
    {
        if (!empty($this->accessToken->access_token)) {
            return $this->accessToken;
        }

        $requestData = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->apiKey,
            'client_secret' => $this->apiSecret
        ];

        $tokenReponse = $this->makeRequest('accessToken', $requestData, 'GET', true);

        if ($tokenReponse->status() == 200) {
            $this->accessToken = $tokenReponse->response();
        }

        return $tokenReponse;
    }

    /**
     * Sets the access token for this API
     *
     * @param string $token The token to be set for this API
     */
    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }


    /**
     * Gets the data from the last request made by this API
     *
     * @return array The data from the last request
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Returns the url for a user to approve access for the app
     *
     * @param array $scope A list of scopes for which to request access
     * @return string The permission granting url
     */
    public function getPermissionUrl($scope = null)
    {
        $requestData = [
            'response_type' => 'code',
            'client_id' => $this->apiKey,
            'redirect_uri' => $this->redirectUri,
            'state' => time(),
        ];

        if ($scope) {
            $requestData['scope'] = $scope;
        }

        return $this->oauthUrl . '/authorization?' . http_build_query($requestData);
    }


    /**
     * Makes a post request to the api
     *
     * @param string $action The api endpoint for the request
     * @param array $data The data to send with the request
     * @return string The access token
     */
    public function post($action, array $data = [])
    {
        return $this->makeRequest($action, $data, 'POST');
    }

    /**
     * Makes a get request to the api
     *
     * @param string $action The api endpoint for the request
     * @param array $data The data to send with the request
     * @return string The access token
     */
    public function get($action, array $data = [])
    {
        return $this->makeRequest($action, $data, 'GET');
    }

    /**
     * Posts a share to LinkedIn using the previously authorized user profile
     *
     * @param array $data An array of data describing the post on LinkedIn including
     *  - content: A collection of fields describing the shared content.
 	 *  - - title: The title of the content being shared.
 	 *  - - description	The description of the content being shared.	256
 	 *  - - submitted-url: A fully qualified URL for the content being shared.
 	 *  - - submitted-image-url: A fully qualified URL to a thumbnail image to accompany the shared content.
     *  - comment: A comment by the member to associated with the share.
     *      If none of the above content parameters are provided, the comment must contain a URL to the content you want
     *      to share.  If the comment contains multiple URLs, only the first one will be analyzed for content to share.
     *  - visibility: A collection of visibility information about the share.
     *  - - code One of the following values:
     *      anyone:  Share will be visible to all members.
     *      connections-only:  Share will only be visible to connections of the member performing the share.
     */
    public function share(array $data) {
        return $this->post('v1/people/~/shares', $data);
    }
}