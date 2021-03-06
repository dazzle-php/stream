<?php

namespace Dazzle\Stream\Test\TUnit;

use Dazzle\Loop\Loop;
use Dazzle\Loop\LoopInterface;
use Dazzle\Loop\Model\SelectLoop;
use Dazzle\Stream\StreamReader;

class StreamReaderTest extends StreamSeekerTest
{
    public function testApiRead_ReadsDataProperly()
    {
        if (substr(PHP_VERSION, 0, 3) === '5.6' && extension_loaded('xdebug'))
        {
            $this->markTestSkipped(
                'This test for some reason fails on Travis CI with PHP-5.6 and xdebug enabled and ONLY on master branch.'
            );
            return;
        }

        $loop = new Loop(new SelectLoop);
        $stream = $this->createStreamReaderMock(null, $loop);
        $resource = $stream->getResource();

        $expectedData = "foobar\n";
        $capturedData = null;
        $capturedOrigin = null;

        $stream->on('data', function($origin, $data) use(&$capturedOrigin, &$capturedData) {
            $capturedOrigin = $origin;
            $capturedData = $data;
        });
        $stream->on('end', $this->expectCallableOnce());
        $stream->read();

        fwrite($resource, $expectedData);
        rewind($resource);

        $loop->addTimer(1e-1, function() use($loop) {
            $loop->stop();
        });
        $loop->start();

        $this->assertSame($expectedData, $capturedData);
        $this->assertSame($stream, $capturedOrigin);

        unset($loop);
    }

    public function testApiHandleRead_ReturnsProperHandler()
    {
        $loop = new Loop(new SelectLoop);
        $stream = $this->createStreamReaderMock(null, $loop);

        $expected = [ $stream, 'handleRead' ];
        $actual = $this->callProtectedMethod($stream, 'getHandleReadFunction');

        $this->assertSame($expected, $actual);
        $this->assertTrue(is_callable($expected));
    }

    /**
     * @param resource|null $resource
     * @param LoopInterface|null $loop
     * @return StreamReader
     */
    protected function createStreamReaderMock($resource = null, $loop = null)
    {
        return new StreamReader(
            is_null($resource) ? fopen('php://temp', 'r+') : $resource,
            is_null($loop) ? $this->createLoopMock() : $loop
        );
    }
}
