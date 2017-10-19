# Laravel Storage Virtual File System Adapter
Sometimes, when we are testing, it would be very nice to just easily swap out the various `Storage:disk()` calls with a
virtual files system like `mikey179/vfsStream`.

## Requirements
This package has been tested against Laravel/Lumen versions 5.5. Check older versions of this package for older Laravel support.

## Installation

```
composer require stechstudio/laravel-vfs-adapter
```
Then register the provider in your `App\Providers\AppServiceProvider` like so:
```php
public function register()
{
    if ($this->app->environment() === 'testing') {
        if (class_exists(VfsFilesystemServiceProvider::class)) {
            $this->app->register(VfsFilesystemServiceProvider::class);
        }
    }
}
```
This assumes you stick with the default `APP_ENV=testing` settings that Laravel comes with out of the box. Of course,
you may also register the provider in the more traditional manner if you want the virtual file system adapter available
in other environments, of it you just don't like the way we do it.

## Configuration
All configuration can be done in a combination of your `.env`, `phpunit.xml`, and `config/filesystems.php`.

Suppose you had a `config/filesystems.php` that looked something like this:

```php
return [
    ....

    'disks' => [
        'data' => [
            'driver' => 'local',
            'root'   => env('STORAGE_DATA_DIR'),
        ],

        'archive' => [
            'driver'   => 's3'
            'key'      => env('S3_KEY'),
            'secret'   => env('S3_SECRET'),
            'region'   => env('S3_REGION'),
            'bucket'   => env('S3_DEVELOP_BUCKET')
        ]
    ],
];
```
Simply make a few modifications:
```php
return [
    ....

    'disks' => [

        'data' => [
            'driver' => env('STORAGE_DATA_DRIVER', 'local'),
            'root'   => env('STORAGE_DATA_DIR'),
            'dir_name' => 'data'
        ],

        'archive' => [
            'driver'   => env('S3_ARCHIVE_DRIVER', 's3')
            'key'      => env('S3_KEY'),
            'secret'   => env('S3_SECRET'),
            'region'   => env('S3_REGION'),
            'bucket'   => env('S3_DEVELOP_BUCKET'),
            'dir_name' => 'archive'
        ]
    ],
];

```
The `dir_name` determines the root directory of the vfsStream for that particular `Storage::disk()`, I like to name it
something relative to the actual `disk` name. Setting up the driver with `env()` allows us to default to our standard drivers
or allow us to override that in `phpunit.xml` to switch over to the virtual filesystem driver.

Now, in your `phpunit.xml` add:

```xml
<env name="STORAGE_DATA_DRIVER" value="vfs"/>
<env name="S3_ARCHIVE_DRIVER" value="vfs"/>
```
That is all there is to it, those drives will now use the virtual filesystem adapter.

