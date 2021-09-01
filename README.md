# Maleficarum HTTP Client

## Exceptions

To catch any kind of exception caused by this client just
```php
} catch (\Maleficarum\Client\Http\Exception\HttpClientException $ex) {
```

You can get the HTTP response details for any `\Maleficarum\Client\Http\Exception\TransferException` using:
```php
$exception->getStatusCode();
$exception->getRawResponse();
$exception->getRequestMethod();
$exception->getUrl();
```

# Change Log
This is the Maleficarum HTTP Client implementation. 

## [5.1.1] - 2021-09-01
### Added 
- Automatic injection of context tracking headers
- Added dependency to context tracking library

## [5.0.2] - 2021-01-20
### Changed
- Removed raw response from TransferException

## [5.0.1] - 2019-05-07
- Add to TransferException message information about http status code, request method, request url and raw response

## [5.0.0] - 2018-10-10
### Changed
- Complete rewrite of the package. New features:
	- Rest client multi mode implemented. (Better performance for high quantity api calls)
	- Client level load balancing implemented for both client modes - when creating a new client
	  instance you pass a set of IPs to load balance over for the specified base url.
	- Added manual DNS resolve mode for improved performance when the base URL api definition
	  is constant.

## [4.0.0] - 2018-06-14
### Changed
- This release provides new HTTP client implementation
    - Removed Curl class
    - Request exceptions has been replaced by TransferException
    - Added helper methods for GET, POST, PUT, PATCH and DELETE requests
    - Added functionality of modifying request options before request is performed (middleware)

## [3.0.0] - 2017-11-22
### Added
- Exceptions improved
    - `\Maleficarum\Client\Http\Exception\HttpClientException` lets catching any kind of exception raised by the client
    - Exception's code reflects the HTTP response code.
    - Runtime and Logic exceptions used in a better way

## [2.0.2] - 2017-10-24
### Added
- Added response body to request exception message

## [2.0.1] - 2017-09-11
### Fixed
- Fix redirects handling
- Remove type hint for setBody and getBody methods

## [2.0.0] - 2017-08-03
### Changed
- Make use of nullable types provided in PHP 7.1 (http://php.net/manual/en/migration71.new-features.php)

## [1.2.1] - 2017-08-09
### Added
- Set default operation timeout

## [1.2.0] - 2017-07-07
### Added
- AbstractClient with possibility to set timeouts
- BasicClient

## [1.1.0] - 2017-04-21
### Added
- Add PATCH method handling

## [1.0.3] - 2016-10-06
### Fixed
- Fixed headers parsing

### Added
- Added getter for request information

## [1.0.2] - 2016-10-04
### Changed
- Replaced invalid CURLOPT_HEADER option with the valid one - CURLOPT_HTTPHEADER
- Changed methods visibility

## [1.0.1] - 2016-09-27
### Added
- Added missing coma in composer.json file

## [1.0.0] - 2016-09-27
### Added
- This was an initial release
