# GoogleDrive

Google Drive class to upload files directly from your application to your Google Drive.

Made available by The Coding Company

https://thecodingcompany.se

Build by:  Victor Angelier <vangelier \u0040 hotmail.com>

#Install/Composer

Easy:  composer require thecodingcompany/googledrive

#Example
```
require_once('google-api-php-client-2.0.0-RC7/vendor/autoload.php');

$drive = new CodingCompany\GoogleDrive();
$drive->setCredentials('location/to/your/credential.json');

$drive->init_google_drive();
print_r($drive->quota());

```