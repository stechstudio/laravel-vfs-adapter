<?php namespace STS\Filesystem;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use League\Flysystem\Adapter\Local;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use SplFileInfo;

/**
 * @property string $dirName
 * @property string $dir_name
 * @property int $dirPermissions
 * @property int $dir_permissions
 * @property array $dir_structure
 * @property int $writeFlags
 * @property int $write_flags
 * @property array $permissions
 * @property int $linkHandling
 * @property int $link_handling
 * @property array $dirStructure
 */
class VirtualFilesystemAdapter extends Local {

    /**
     * This adapters configuration
     * @var Collection
     */
    protected $config;

    /**
     * Default Configuration
     * @var array
     */
    protected $defaultConfig = [
        'dir_name' => 'root',
        'dir_permissions' => 0755,
        'dir_structure' => [],
        'write_flags' => LOCK_EX,
        'link_handling' => self::DISALLOW_LINKS,
        'permissions' => [
            'file' => [
                'public' => 0644,
                'private' => 0600,
            ],
            'dir' => [
                'public' => 0755,
                'private' => 0700,
            ]
        ]
    ];

    /**
     * @var vfsStreamDirectory
     */
    protected $vfsStreamDir;

    /**
     * VirtualFilesystemAdapter constructor!
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = collect(array_replace_recursive($this->defaultConfig, $config));
        $this->vfsStreamDir = vfsStream::setup($this->dirName, $this->dirPermissions, $this->dirStructure);
        parent::__construct($this->vfsStreamDir->url(), $this->writeFlags, $this->linkHandling, $this->permissions);
    }

    /**
     * Get a look at the current configuration
     * @return Collection
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get a look at the default configuration
     * @return array
     */
    public function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    /**
     * Magic Getter to access our
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        $response = $this->config->get(Str::snake($name));

        if (is_null($response)){
            throw new \InvalidArgumentException(sprintf('%s is not a valid field.', $name));
        }

        return $response;
    }

    /**
     * @return vfsStreamDirectory
     */
    public function getVfsStreamDir()
    {
        return $this->vfsStreamDir;
    }

    /**
     * Ensure the root directory exists. Because we are using vfs, if this is the root
     * then just go ahead and return. Otherwise, carry on as usual.
     *
     * @param string $path root directory path
     * @return void
     * @throws \Exception in case the root directory can not be created
     */
    protected function ensureDirectory($path)
    {
        if ($path === $this->dirName){
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }
        parent::ensureDirectory($path);
    }

    /**
     * vfsStream has issues with SplFileInfo::getRealPath(), so we will just use
     * SplFileInfo::getPathname()
     * @param SplFileInfo $file
     */
    protected function deleteFileInfoObject(SplFileInfo $file)
    {
        unlink($file->getPathname());
    }
}
