<?php

namespace Dazzle\Stream\Test\TModule\Stream\Sync;

use Dazzle\Stream\Sync\Stream;
use Dazzle\Stream\Sync\StreamReader;
use Dazzle\Stream\Sync\StreamWriter;
use Dazzle\Stream\Test\TModule;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class StreamTest extends TModule
{
    public function tearDown()
    {
        $local = $this->basePath();
        unlink("$local/temp");

        parent::tearDown();
    }

    public function testStream_WriteAndReadDataScenario()
    {
        $local = $this->basePath();
        $writer = new Stream(fopen("file://$local/temp", 'w+'));
        $reader = new Stream(fopen("file://$local/temp", 'r+'));

        $expectedData = "qwertyuiop\n";
        $capturedData = null;
        $readData = null;

        $reader->on('data', function($origin, $data) use(&$capturedData) {
            $capturedData = $data;
        });
        $reader->on('end', $this->expectCallableOnce());
        $reader->on('error', $this->expectCallableNever());
        $reader->on('close', $this->expectCallableOnce());

        $writer->on('drain', $this->expectCallableOnce());
        $writer->on('finish', $this->expectCallableOnce());
        $writer->on('error', $this->expectCallableNever());
        $writer->on('close', $this->expectCallableOnce());

        $writer->write($expectedData);
        $readData = $reader->read();

        $writer->close();
        $reader->close();

        $this->assertEquals($expectedData, $readData);
        $this->assertEquals($expectedData, $capturedData);
    }

    public function testStreamReader_StreamWriter_WriteAndReadDataScenario()
    {
        $local = $this->basePath();
        $writer = new StreamWriter(fopen("file://$local/temp", 'w+'));
        $reader = new StreamReader(fopen("file://$local/temp", 'r+'));

        $expectedData = "qwertyuiop\n";
        $capturedData = null;
        $readData = null;

        $reader->on('data', function($origin, $data) use(&$capturedData) {
            $capturedData = $data;
        });
        $reader->on('drain', $this->expectCallableNever());
        $reader->on('error', $this->expectCallableNever());
        $reader->on('close', $this->expectCallableOnce());

        $writer->on('data',  $this->expectCallableNever());
        $writer->on('drain', $this->expectCallableOnce());
        $writer->on('error', $this->expectCallableNever());
        $writer->on('close', $this->expectCallableOnce());

        $writer->write($expectedData);
        $readData = $reader->read();

        $writer->close();
        $reader->close();

        $this->assertEquals($expectedData, $readData);
        $this->assertEquals($expectedData, $capturedData);
    }
}
