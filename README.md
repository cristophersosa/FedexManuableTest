# FedexManuableTest by Cristopher Sosa

### To install:
```
composer require cristophersosa/fedex-manuable-test
````
### To use:

Import library
```
require 'vendor/autoload.php';
use Cristophersosa\FedexManuableTest\Fedex;
```
Create Fedex object with user credentials
```
$fedex = new Fedex('KEY', 'PASSWORD', ACCOUNT_NUMBER, METER_NUMBER, LANGUAGE, LOCALE);
```
Get rates array with getRates function
```
$fedex->getRates($params);
```
Params example:
```
[
	"address_from"=> [
		"zip"=> "64000",
		"country"=> "MX"
	],
	"address_to"=> [
		"zip"=> "64000",
		"country"=> "MX"
	],
	"parcel"=> [
		"length"=> 25.0,
		"width"=> 28.0,
		"height"=> 46.0,
		"distance_unit"=> "cm",
		"weight"=> 6.5,
		"mass_unit"=> "kg"
	]
]
```
