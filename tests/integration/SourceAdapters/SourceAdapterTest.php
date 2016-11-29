<?php

use Plank\Mediable\Stream;
use Plank\Mediable\SourceAdapters\FileAdapter;
use Plank\Mediable\SourceAdapters\FileStreamAdapter;
use Plank\Mediable\SourceAdapters\HttpStreamAdapter;
use Plank\Mediable\SourceAdapters\IoStreamAdapter;
use Plank\Mediable\SourceAdapters\StringAdapter;
use Plank\Mediable\SourceAdapters\UploadedFileAdapter;
use Plank\Mediable\SourceAdapters\LocalPathAdapter;
use Plank\Mediable\SourceAdapters\RemoteUrlAdapter;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Http\Message\StreamInterface;

class SourceAdapterTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app['filesystem']->disk('uploads')->put('plank.png', fopen(__DIR__.'/../../_data/plank.png', 'r'));
    }

    public function adapterProvider()
    {
        $file = realpath(__DIR__.'/../../_data/plank.png');
        $string = file_get_contents($file);
        $url = 'https://www.plankdesign.com/externaluse/plank.png';
        $fileResource = fopen($file, 'rb');
        $fileStream = new Stream(fopen($file, 'rb'));
        $httpResource = fopen($url, 'rb');
        $memoryResource = fopen('php://memory', 'w+b');
        fwrite($memoryResource, $string);
        rewind($memoryResource);
        $data = [
            [FileAdapter::class, new File($file), $file, 'plank'],
            [UploadedFileAdapter::class, new UploadedFile($file, 'plank.png', 'image/png', 8444, UPLOAD_ERR_OK, true), $file, 'plank'],
            [LocalPathAdapter::class, $file, $file, 'plank'],
            [RemoteUrlAdapter::class, $url, $url, 'plank'],
            [StringAdapter::class, $string, null, null],
            [FileStreamAdapter::class, $fileResource, $file, 'plank'],
            [FileStreamAdapter::class, $fileStream, $file, 'plank'],
            [HttpStreamAdapter::class, $httpResource, $url, 'plank'],
            [IoStreamAdapter::class, $memoryResource, 'php://memory', null],
        ];
        return $data;
    }

    public function invalidAdapterProvider()
    {
        $file = __DIR__ . '/../../_data/invalid.png';
        $url = 'https://www.plankdesign.com/externaluse/invalid.png';

        return [
            [new FileAdapter(new File($file, false))],
            [new LocalPathAdapter($file)],
            [new UploadedFileAdapter(new UploadedFile($file, 'invalid.png', 'image/png', 8444, UPLOAD_ERR_CANT_WRITE, false))],
            [new FileStreamAdapter(@fopen($file, 'r'))],
            [new HttpStreamAdapter(@fopen($url, 'r'))],
        ];
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_can_return_source($adapter, $source)
    {
        $adapter = new $adapter($source);
        $this->assertEquals($source, $adapter->getSource());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_absolute_path($adapter, $source, $path)
    {
        $adapter = new $adapter($source);
        $this->assertEquals($path, $adapter->path());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_filename($adapter, $source, $path, $filename)
    {
        $adapter = new $adapter($source);
        $this->assertEquals($filename, $adapter->filename());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_extension($adapter, $source)
    {
        $adapter = new $adapter($source);
        $this->assertEquals('png', $adapter->extension());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_mime_type($adapter, $source)
    {
        $adapter = new $adapter($source);
        $this->assertEquals('image/png', $adapter->mimeType());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_file_contents($adapter, $source)
    {
        $adapter = new $adapter($source);
        $contents = $adapter->contents();

        if (is_resource($contents)) {
            $this->assertEquals(get_resource_type($contents), 'stream');
        } else {
            $this->assertInternalType('string', $contents);
        }
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_adapts_file_size($adapter, $source)
    {
        $adapter = new $adapter($source);
        $this->assertEquals(8444, $adapter->size());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_it_verifies_file_validity($adapter, $source)
    {
        $adapter = new $adapter($source);
        $this->assertTrue($adapter->valid());
    }

    /**
     * @dataProvider invalidAdapterProvider
     */
    public function test_it_verifies_file_validity_failure($adapter)
    {
        $this->assertFalse($adapter->valid());
    }
}
