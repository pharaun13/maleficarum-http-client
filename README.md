# Change Log
This is the Maleficarum HTTP Client implementation. 

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
