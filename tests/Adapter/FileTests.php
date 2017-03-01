<?php namespace STS\Filesystem;

use League\Flysystem\Config;
use League\Flysystem\File;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class FileTests extends \PHPUnit_Framework_TestCase
{
    /** @var Filesystem */
    protected $filesystem;

    public function setup()
    {
        clearstatcache();
        $fs = new VirtualFilesystemAdapter();
        $fs->deleteDir('files');
        $fs->createDir('files', new Config());
        $fs->write('file.txt', 'contents', new Config());
        $this->filesystem = new Filesystem($fs);
    }

    public function tearDown()
    {
        try {
            $this->filesystem->delete('file.txt');
        } catch (FileNotFoundException $e) {
        }
        $this->filesystem->deleteDir('files');
    }

    /**
     * @return File
     */
    protected function getFile()
    {
        return $this->filesystem->get('file.txt');
    }

    /** @test */
    public function file_exists()
    {
        $file = $this->getFile();
        $this->assertTrue($file->exists());
    }

    /** @test */
    public function file_can_be_read()
    {
        $file = $this->getFile();
        $contents = $file->read();
        $this->assertEquals('contents', $contents);
    }

    /** @test */
    public function can_obtain_a_read_stream_resource()
    {
        $file = $this->getFile();
        $this->assertInternalType('resource', $file->readStream());
    }

    /** @test */
    public function can_write_to_file()
    {
        $file = new File();
        $this->filesystem->get('new.txt', $file);
        $file->write('new contents');
        $this->assertEquals('new contents', $file->read());
    }

    /** @test */
    public function can_write_to_stream()
    {
        $file = new File();
        $this->filesystem->get('new.txt', $file);
        $resource = tmpfile();
        fwrite($resource, 'stream contents');
        $file->writeStream($resource);
        $this->assertEquals('stream contents', $file->read());
    }

    /** @test */
    public function can_update_file_contents()
    {
        $file = $this->getFile();
        $file->update('new contents');
        $this->assertEquals('new contents', $file->read());
    }

    /** @test */
    public function can_update_a_file_stream()
    {
        $file = $this->getFile();
        $resource = tmpfile();
        fwrite($resource, 'stream contents');
        $file->updateStream($resource);
        fclose($resource);
        $this->assertEquals('stream contents', $file->read());
    }

    /** @test */
    public function can_put_contents_in_file()
    {
        $file = new File();
        $this->filesystem->get('files/new.txt', $file);
        $file->put('new contents');
        $this->assertEquals('new contents', $file->read());
        $file->put('updated content');
        $this->assertEquals('updated content', $file->read());
    }

    /** @test */
    public function can_put_contents_in_file_stream()
    {
        $file = new File();
        $this->filesystem->get('files/new.txt', $file);
        $resource = tmpfile();
        fwrite($resource, 'stream contents');
        $file->putStream($resource);
        fclose($resource);
        $this->assertEquals('stream contents', $file->read());
        $resource = tmpfile();
        fwrite($resource, 'updated stream contents');
        $file->putStream($resource);
        fclose($resource);
        $this->assertEquals('updated stream contents', $file->read());
    }

    /** @test */
    public function can_rename_file()
    {
        $file = $this->getFile();
        $result = $file->rename('files/renamed.txt');
        $this->assertTrue($result);
        $this->assertFalse($this->filesystem->has('file.txt'));
        $this->assertTrue($this->filesystem->has('files/renamed.txt'));
        $this->assertEquals('files/renamed.txt', $file->getPath());
    }

    /** @test */
    public function ensure_rename_fails()
    {
        $adapter = $this->createMock('League\Flysystem\AdapterInterface');
        $adapter
            ->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(
                ['file.txt'],
                ['files/renamed.txt']
            )
            ->willReturnOnConsecutiveCalls(true, false);
        $adapter
            ->expects($this->once())
            ->method('rename')
            ->with('file.txt', 'files/renamed.txt')
            ->willReturn(false);
        $filesystem = new Filesystem($adapter);
        /** @var File $file */
        $file = $filesystem->get('file.txt', new File());
        $result = $file->rename('files/renamed.txt');
        $this->assertFalse($result);
        $this->assertEquals('file.txt', $file->getPath());
    }

    /** @test */
    public function ensure_we_can_copy_a_file()
    {
        $file = $this->getFile();
        $copied = $file->copy('files/copied.txt');
        $this->assertTrue($this->filesystem->has('file.txt'));
        $this->assertTrue($this->filesystem->has('files/copied.txt'));
        $this->assertEquals('file.txt', $file->getPath());
        $this->assertEquals('files/copied.txt', $copied->getPath());
    }

    /** @test */
    public function ensure_copy_fails()
    {
        $adapter = $this->createMock('League\Flysystem\AdapterInterface');
        $adapter
            ->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(
                ['file.txt'],
                ['files/copied.txt']
            )
            ->willReturnOnConsecutiveCalls(true, false);
        $adapter
            ->expects($this->once())
            ->method('copy')
            ->with('file.txt', 'files/copied.txt')
            ->willReturn(false);
        $filesystem = new Filesystem($adapter);
        /** @var File $file */
        $file = $filesystem->get('file.txt', new File());
        $result = $file->copy('files/copied.txt');
        $this->assertFalse($result);
    }

    /** @test */
    public function check_file_timestamp()
    {
        $file = $this->getFile();
        $timestamp = $this->filesystem->getTimestamp($file->getPath());
        $this->assertEquals($timestamp, $file->getTimestamp());
    }

    /** @test */
    public function check_file_mimetype()
    {
        $file = $this->getFile();
        $mimetype = $this->filesystem->getMimetype($file->getPath());
        $this->assertEquals($mimetype, $file->getMimetype());
    }

    /** @test */
    public function check_file_visibility()
    {
        $file = $this->getFile();
        $visibility = $this->filesystem->getVisibility($file->getPath());
        $this->assertEquals($visibility, $file->getVisibility());
    }

    /** @test */
    public function check_file_metadata()
    {
        $file = $this->getFile();
        $metadata = $this->filesystem->getMetadata($file->getPath());
        $this->assertEquals($metadata, $file->getMetadata());
    }

    /** @test */
    public function check_file_size()
    {
        $file = $this->getFile();
        $size = $this->filesystem->getSize($file->getPath());
        $this->assertEquals($size, $file->getSize());
    }

    /** @test */
    public function ensure_we_can_delete_file()
    {
        $file = $this->getFile();
        $file->delete();
        $this->assertFalse($this->filesystem->has('file.txt'));
    }
}