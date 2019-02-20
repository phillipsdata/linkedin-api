# phillipsdata/linkedin-api

This library allows you to communicate with the LinkedIn APIs.  It is very generic and depends on the user to know their target endpoints and the data to be submitted.

## Installation

Install via composer:

```sh
composer require phillipsdata/linkedin
```

## Usage

To start, instantiate the class

```php
$linkedin = new LinkedIn(
    'LINKEDIN_API_KEY',
    'LINKEDIN_API_SECRET',
    'LINKEDIN_CALLBACK_URL'
);

```

Get a URL to visit and be granted permissions from

```php
$permissions_url = $linkedin->getPermissionUrl(
    array(
        'r_basicprofile',
        'r_liteprofile',
        'w_share',
        'w_member_social'
    )
);

```

The parameter here is a list of 'scopes'.  If granted, they determine which API calls you are authorized to make.

API v1 Permissions
 - r_basicprofile
 - r_emailaddress
 - w_share
 - rw_company_admin

API v2 Permissions
 - r_liteprofile (replaces r_basicprofile)
 - r_emailaddress
 - w_member_social (replaces w_share)

After visiting the $permission_url, you will be redirected to the 'LINKEDIN_CALLBACK_URL' you submitted to the constructor.
The redirect will submit a 'code' get parameter to that location which can be used to generate an access token that will be used to grant permission for future API calls.

```php
$tokenResponse = $linkedin->getAccessToken($_GET['code']);

if ($tokenResponse->status() == 200) {
  // Record $tokenResponse->response() in some way
} else {
  echo $tokenResponse->errors();
}

```

This will return the access token if you want to store it somehow.
Additionally it will set the token on your current LinkedIn object which will use it for any API calls you make.

After this you can make any api call you like as long you know the endpoint and data required

See the [docs here](https://docs.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/share-on-linkedin?context=linkedin/consumer/context#create-a-text-share) for a full description of making a share request

```php
$data = [
    'author' => 'urn:li:person:123456',
    'lifecycleState' => 'PUBLISHED',
    'specificContent' => [
        'com.linkedin.ugc.ShareContent' => [
            'shareCommentary' => [
                'text' => "Leverage LinkedIn's APIs to maximize engagement"
            ],
            'shareMediaCategory' => 'NONE'
        ]
    ],
    'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC']
];

$shareResponse = $linkedin->post('v2/ugcPosts', $data);
```
The response is returned as an LinkedInAPIResponse object that can be accessed like this

```php
$shareResponse->headers(); // An array of header fields and their values
$shareResponse->raw(); // Exactly what was returned by LinkedIn, including headers
$shareResponse->response(); // An object containing the data returned by LinkedIn
$shareResponse->errors(); // Any errors given in the response
$shareResponse->status(); // The status code returned by the request
```

The API also has a method called share() which take a little bit of work off of the user by defaulting fields like 'author' and 'lifecycleState'.

This method worked different pre v2.x.  Instead it used version 1 of the LinkedIn api and simply defaulted the endpoint.

```php
$data = [
    'specificContent' => [
        'com.linkedin.ugc.ShareContent' => [
            'shareCommentary' => [
                'text' => "Leverage LinkedIn's APIs to maximize engagement"
            ],
            'shareMediaCategory' => 'NONE'
        ]
    ],
    'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC']
];

$shareResponse = $linkedin->share($data);
```
