<?php

namespace Dazzle\Stream\Test\TUnit;

use Dazzle\Loop\Model\SelectLoop;
use Dazzle\Loop\Loop;
use Dazzle\Loop\LoopInterface;
use Dazzle\Stream\StreamWriter;

class StreamWriterTest extends StreamSeekerTest
{
    public function testApiWrite_WritesDataProperly()
    {
        if (substr(PHP_VERSION, 0, 3) === '5.6' && extension_loaded('xdebug'))
        {
            $this->markTestSkipped(
                'This test for some reason fails on Travis CI with PHP-5.6 and xdebug enabled and ONLY on master branch.'
            );
            return;
        }

        $loop = new Loop(new SelectLoop);
        $stream = $this->createStreamWriterMock(null, $loop);
        $resource = $stream->getResource();

        $expectedData = str_repeat('X', (int) $stream->getBufferSize()*1.5);

        $stream->on('drain', $this->expectCallableTwice());
        $stream->on('finish', $this->expectCallableOnce());

        $stream->write(substr($expectedData, 0, 1024));
        $stream->write(substr($expectedData, 1024));

        $loop->addTimer(1e-1, function() use($loop) {
            $loop->stop();
        });
        $loop->start();

        $stream->rewind();
        $this->assertSame($expectedData, fread($resource, (int) $stream->getBufferSize()*2));

        unset($loop);
    }

    public function testApiHandleWrite_ReturnsProperHandler()
    {
        $loop = new Loop(new SelectLoop);
        $stream = $this->createStreamWriterMock(null, $loop);

        $expected = [ $stream, 'handleWrite' ];
        $actual = $this->callProtectedMethod($stream, 'getHandleWriteFunction');

        $this->assertSame($expected, $actual);
        $this->assertTrue(is_callable($expected));
    }

    /**
     * @param resource|null $resource
     * @param LoopInterface|null $loop
     * @return StreamWriter
     */
    protected function createStreamWriterMock($resource = null, $loop = null)
    {
        return new StreamWriter(
            is_null($resource) ? fopen('php://temp', 'r+') : $resource,
            is_null($loop) ? $this->createWritableLoopMock() : $loop
        );
    }
}
