# api-client-php


## Installation

```bash
composer require resellerinterface/api-client-php
```

## Example
```php
require( 'vendor/autoload.php' );

use ResellerInterface\Api\Client;

// create a new client
$client = new Client();

// login
$client->login( "username", "password", 23456 ); // username, password, resellerId

// make a request
$response = $client->request( "reseller/details", ['resellerID' => 'OWN']);
var_dump($response->getData());
// response
//[
//	'time' => 12,
//	'state' => 1000,
//	'stateName' => 'OK',
//	'stateParam' => '',
//	'reseller' => [
//		'resellerID' => 23456,
//		'parentID' => 23455,
//		'state' => 'ACTIVE',
//		'company' => 'Acme Corp.',
//		'firstname' => 'John',
//		'lastname' => 'Doe',
//		'street' => 'Mainstreet',
//		'number' => '1223',
//		'postcode' => '10115',
//		'city' => 'Berlin',
//		'country' => 'DE',
//		'mail' => 'info@example.org',
//		'phone' => '+491234567890',
//		'fax' => '',
//		'parents' => [
//			0 => 23455,
//		],
//		'settings' => [
//			'group' => [
//				'name' => 'value',
//			],
//		],
//	],
//	'user' => [
//		'userID' => 12345,
//		'mainUser' => true,
//		'state' => 'ACTIVE',
//		'username' => 'User',
//		'password' => '****',
//		'settings' => [
//			'group' => [
//				'name' => 'value',
//			],
//		],
//		'rightsCategory' => 51,
//		'rightsGroups' => [
//			0 => 912,
//			1 => 913,
//		],
//		'directRights' => [
//			'category' => [
//				'group' => [
//					'function' => true,
//				],
//			],
//		],
//		'rights' => [
//			'category' => [
//				'group' => [
//					'function' => true,
//				],
//			],
//		],
//	],
//]
```