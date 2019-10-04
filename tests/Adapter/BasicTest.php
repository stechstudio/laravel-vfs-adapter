<?php namespace STS\Filesystem;

use Illuminate\Support\Str;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase {

    protected $confg_1 = [
        'dir_name' => 'php_unit_root',
        'dir_permissions' => 0700,
        'dir_structure' => [],
        'write_flags' => 0,
        'link_handling' => Local::DISALLOW_LINKS,
        'permissions' => [
            'file' => [
                'public' => 0660,
                'private' => 0640,
            ],
            'dir' => [
                'public' => 0775,
                'private' => 0700,
            ]
        ]
    ];

    /** @test */
    public function adapter_instantiates_with_default_settings(){
        $adapter = new VirtualFilesystemAdapter();
        $this->assertEquals($adapter->getDefaultConfig(), $adapter->getConfig()->toArray());
    }

    /** @test */
    public function adapter_instantiates_with_custom_settings(){
        $adapter = new VirtualFilesystemAdapter($this->confg_1);
        $this->assertNotEquals($adapter->getDefaultConfig(), $adapter->getConfig()->toArray());
        $this->assertEquals($this->confg_1, $adapter->getConfig()->toArray());
    }

    /** @test */
    public function magic_getters_access_configs_with_snake_case(){
        $adapter = new VirtualFilesystemAdapter($this->confg_1);
        collect($this->confg_1)->each(
          function ($value, $key) use ($adapter) {
              $this->assertEquals($value, $adapter->$key);
          }
        );
    }

    /** @test */
    public function magic_getters_access_configs_with_camel_case(){
        $adapter = new VirtualFilesystemAdapter($this->confg_1);
        collect($this->confg_1)->each(
            function ($value, $key) use ($adapter) {
                $this->assertEquals($value, $adapter->{Str::camel($key)});
            }
        );
    }

    /** @test */
    public function throws_exception_on_bad_parameter(){
        $adapter = new VirtualFilesystemAdapter();
        try{
            $adapter->fooBar;
        }catch (\InvalidArgumentException $e){
            $this->assertEquals('fooBar is not a valid field.', $e->getMessage());
        }
    }

    /** @test */
    public function setup_new_vfs_directory_structure(){
        $filesystem = new Filesystem(new VirtualFilesystemAdapter());
        $filesystem->put('foo/bar/tile1.txt', 'FooBar');

        $expected_1 = [
            "type" => "file",
            "path" => "foo/bar/tile1.txt",
            "timestamp" => 1488331955,
            "size" => 6,
            "dirname" => "foo/bar",
            "basename" => "tile1.txt",
            "extension" => "txt",
            "filename" => "tile1",
          ];
        $actual_1 = $filesystem->listContents('foo/bar')[0];

        collect($expected_1)->each(
          function ($value, $key) use ($actual_1) {
              if ($key === 'timestamp'){
                  return;
              }
              $this->assertEquals($value, $actual_1[$key]);
          }
        );

        $newStructure = [
            'Core' => [
                'AbstractFactory' => [
                    'test.php' => 'some text content',
                    'other.php' => 'some other text content',
                    'invalid.php' => 'yet some more text content'
                ],
                'AnEmptyFolder' => [],
                'somethingwicked.php' => 'some bad voodoo'
            ]
        ];

        $config = $filesystem->getAdapter()->getConfig();
        $config['dir_structure'] = $newStructure;
        vfsStream::setup($config['dir_name'], $config['dir_permissions'], $config['dir_structure']);
        // Ensure the old file system is gone.
        $this->assertFalse($filesystem->has('foo/bar'));

        $this->assertTrue($filesystem->has('Core/AbstractFactory/test.php'));
        $this->assertEquals('some other text content', $filesystem->read('Core/AbstractFactory/other.php'));
        $this->assertEquals('some bad voodoo', $filesystem->read('Core/somethingwicked.php'));
    }

    /** @test */
    public function setup_new_vfs_from_local_filesystem()
    {
        $filesystem = new Filesystem(new VirtualFilesystemAdapter());
        vfsStream::copyFromFileSystem(__DIR__ . '/../resources/filesystemcopy', $filesystem->getAdapter()->getVfsStreamDir(), 3145728);

        $this->assertTrue($filesystem->has('withSubfolders/subfolder2/multipage2.pdf'));
        $this->assertEquals('application/pdf', $filesystem->getMimetype('withSubfolders/subfolder2/multipage2.pdf'));
        $this->assertEquals('image/tiff', $filesystem->getMimetype('withSubfolders/subfolder2/one.TIF'));
        $this->assertEquals('571382', $filesystem->getSize('withSubfolders/subfolder2/singlepage1.pdf'));
    }
}
