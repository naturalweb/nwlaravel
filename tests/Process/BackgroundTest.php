<?php
namespace Tests\Process;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\Process\Background;

class BackgroundTest extends TestCase
{
    public function testConstruct()
    {
        $process = new Background('/tmp/test.txt');

        $this->assertEquals('/tmp/test.txt', $process->getLog());
        $this->assertAttributeEquals('/tmp/test.txt', 'log', $process);
    }

    public function testSetLogThrowException()
    {
        $this->setExpectedException(\RuntimeException::class);

        $process = new Background;
        $process->setLog('http:/No/Writeable/file');
    }

    public function testStartAndStop()
    {
        $cmd = 'ls '.__DIR__ . '; sleep 3;';

        $process = new Background();
        $this->assertTrue($process->start($cmd));

        $pid = $process->pid($cmd);
        if (!$pid) {
            $this->markTestSkipped('Enviroment not permission Background!');
        }

        $this->assertEquals($pid, $process->getPid());

        $this->assertTrue($process->stop($cmd));
        $this->assertNull($process->getPid());
        $this->assertEmpty($process->pid($cmd));
        $this->assertNull($process->getErrors());
    }

    public function testErr()
    {
        $process = new Background();

        $this->assertTrue($process->start(1));
    }
}
