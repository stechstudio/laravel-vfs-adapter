<?php namespace STS\Filesystem;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider as ServiceProvider;

/**
 * @codeCoverageIgnore
 */
class VfsFilesystemServiceProvider extends  ServiceProvider {

    //boot
    public function boot()
    {
        Storage::extend('vfs',function($app, $config){

            //init the client
            $client = new VirtualFilesystemAdapter($config);

            //lets now call the obj
            $vfsFileSystem = new Filesystem($client);

            //return
            return $vfsFileSystem;
        });

    }//end boot


    public function register()
    {
        //
    }


}