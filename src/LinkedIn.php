<?php
namespace Phillipsdata\LinkedIn;

/**
 * This class integrates with the Consumer Solutions Platform LinkedIn API
 * See the API docs here https://docs.microsoft.com/en-us/linkedin/consumer/
 */
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
     * @return LinkedInResponseInterface The data returned by the request
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
                // Fall through to set post data
            default:
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
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
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        if (curl_errno($ch)) {
            $result .= "\n" . json_encode((object)['error' => 'curl_error', 'error_description' => curl_error($ch)]);
        }
        curl_close($ch);

        // Return request response
        return $oauthRequest
            ? new LinkedInOAuthResponse($result, $headerSize)
            : new LinkedInAPIResponse($result, $headerSize);
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
            'scope' => 'w_share r_basicprofile r_liteprofile w_member_social'
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
     * @return LinkedInResponseInterface
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
     * @return LinkedInResponseInterface
     */
    public function get($action, array $data = [])
    {
        return $this->makeRequest($action, $data, 'GET');
    }

    /**
     * Posts a share to LinkedIn using the previously authorized user profile
     * See https://docs.microsoft.com/en-us/linkedin/marketing/integrations/community-management/shares/ugc-post-api
     *
     * @param string $content The text to be shared
     * @param string $media_type Represents the media assets attached to the share. ('NONE', 'ARTICLE', 'IMAGE')
     * @param array $media_urns A collection of IDs of the uploaded image assets. (Not required for uploading an article)
     * @param string $visibility One of the following values:
     *      PUBLIC: The share will be viewable by anyone on LinkedIn.
     *      CONNECTIONS: The share will be viewable by 1st-degree connections only.
     * @return LinkedInAPIResponse
     */
    public function share($content, $media_type = 'NONE', $media_urns = [], $visibility = 'PUBLIC')
    {
        // Set the author based on the currently authenticated user
        $author_urn = '';
        $userResponse = $this->getUser();
        if ($userResponse->status() == 200) {
            $user = $userResponse->response();

            $author_urn = 'urn:li:person:' . $user->id;
        }

        $data = [
            'author' => $author_urn,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $content
                    ],
                    'shareMediaCategory' => $media_type,
                    'media' => []
                ]
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => $visibility
            ]
        ];

        if ($media_type != 'NONE') {
            foreach ($media_urns as $media_urn) {
                $data['specificContent']['media'][] = (object)[
                    'status' => 'READY',
                    'description' => '',
                    'media' => $media_urn,
                    'title' => (object)['text' => '']
                ];
            }
        }

        return $this->post('v2/ugcPosts', $data);
    }

    /**
     * Gets information for the previously authorized user profile
     *
     * @return LinkedInAPIResponse
     */
    public function getUser()
    {
        return $this->get('v2/me');
    }
}
