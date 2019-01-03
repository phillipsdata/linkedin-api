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
        'r_emailaddress',
        'w_share'
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

```php
$data = array(
  "comment" => "Check out developer.linkedin.com!",
  "content" -> array(
    "title" => "LinkedIn Developers Resources",
    "description" => "Leverage LinkedIn's APIs to maximize engagement",
    "submitted-url" => "https://developer.linkedin.com",  
    "submitted-image-url" => "https://example.com/logo.png"
  ),
  "visibility" => array(
    "code" => "anyone"
  )  
);
$shareResponse = $this->post('v1/people/~/shares', $data);
```

The response is returned as an LinkedInAPIResponse object that can be accessed like this

```php
$shareResponse->raw(); // Exactly what was returned by LinkedIn
$shareResponse->response(); // An object containing the data returned by LinkedIn
$shareResponse->errors(); // Any errors given in the response
$shareResponse->status(); // 200 for a successful response
```