<?php

namespace Dazzle\Stream\Test\TModule\Stream;

use Dazzle\Stream\StreamReaderInterface;
use Dazzle\Stream\StreamWriterInterface;
use Dazzle\Stream\Test\_Simulation\SimulationInterface;
use Dazzle\Stream\Test\TModule;
use ReflectionClass;

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
    }

    /**
     * @dataProvider asyncStreamPairProvider
     * @param string $readerClass
     * @param string $writerClass
     */
    public function testStream_WritesAndReadsDataCorrectly($readerClass, $writerClass)
    {
        $this
            ->simulate(function(SimulationInterface $sim) use($readerClass, $writerClass) {
                $loop = $sim->getLoop();
                $local = $this->basePath();
                $cnt = 0;

                $reader = (new ReflectionClass($readerClass))->newInstance(
                    fopen("file://$local/temp", 'w+'),
                    $loop
                );
                $reader->on('data', function(StreamReaderInterface $conn, $data) use($sim) {
                    $sim->expect('data', $data);
                    $conn->close();
                });
                $reader->on('end', $this->expectCallableOnce());
                $reader->on('drain', $this->expectCallableNever());
                $reader->on('finish', $this->expectCallableNever());
                $reader->on('error', $this->expectCallableNever());
                $reader->on('close', function() use($sim, &$cnt) {
                    $sim->expect('close');
                    if (++$cnt === 2)
                    {
                        $sim->done();
                    }
                });

                $writer = (new ReflectionClass($writerClass))->newInstance(
                    fopen("file://$local/temp", 'r+'),
                    $loop
                );
                $writer->on('data', $this->expectCallableNever());
                $writer->on('end', $this->expectCallableNever());
                $writer->on('drain', function(StreamWriterInterface $writer) use($sim) {
                    $sim->expect('drain');
                    $writer->close();
                });
                $writer->on('finish', $this->expectCallableOnce());
                $writer->on('error', $this->expectCallableNever());
                $writer->on('close', function() use($sim, &$cnt) {
                    $sim->expect('close');
                    if (++$cnt === 2)
                    {
                        $sim->done();
                    }
                });

                $writer->write('message!');
                $reader->read();
            })
            ->expect([
                [ 'drain' ],
                [ 'close' ],
                [ 'data', 'message!' ],
                [ 'close' ]
            ])
        ;
    }

    /**
     * Provider classes of Stream.
     *
     * @return string[][]
     */
    public function asyncStreamPairProvider()
    {
        return [
            [
                'Dazzle\Stream\Stream',
                'Dazzle\Stream\Stream'
            ],
            [
                'Dazzle\Stream\StreamReader',
                'Dazzle\Stream\StreamWriter'
            ]
        ];
    }
}
