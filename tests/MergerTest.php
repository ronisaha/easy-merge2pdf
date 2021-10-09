<?php

namespace EasyMerge2pdfTests;

use EasyMerge2pdf\CommandRuntimeException;
use LogicException;
use PHPUnit\Framework\TestCase;

class MergerTest extends TestCase
{
    public function testInitialization()
    {
        $merger = new ExtendedMerger();
        $options = $merger->getOptions();
        $this->assertEquals(false, $options['auto']);
        $this->assertEquals(null, $options['binary']);
    }

    public function testAutoDiscoverBinary()
    {
        $merger = new ExtendedMerger(['auto' => true]);
        $options = $merger->getOptions();
        $this->assertEquals(true, $options['auto']);
        $this->assertEquals('/usr/local/bin/merge2pdf', $options['binary']);
    }

    public function testInputAssignments()
    {
        $merger = new ExtendedMerger();
        $merger
            ->addInput('a.pdf', '1-3')
            ->addInput('b.pdf', 1);

        $inputs = $merger->getInputs();
        $this->assertEquals('a.pdf~1,2,3', $inputs[0]);
        $this->assertEquals('b.pdf~1', $inputs[1]);
        $merger->clearInputs();
        $this->assertEmpty($merger->getInputs());
    }

    public function testApplyAllOptions()
    {
        $merger = new ExtendedMerger([
            'binary' => 'merge2pdf',
            'size' => 'A4',
            'timeout' => 3000,
            'scale_height' => true,
            'scale_width' => true,
            'jpeg_quality' => 80,
            'margin' => [
                'left' => 10,
                'right' => .2,
                'top' => .5,
                'bottom' => .5,
            ]
        ]);

        $merger->addInput('b.pdf', 1);

        $commandOptions = $merger->getCommandOptions();

        $this->assertContains('--jpeg-quality=80', $commandOptions);
        $this->assertContains('10,0.2,0.5,0.5', $commandOptions);
    }

    public function testExecuteCommand()
    {
        $this->expectException(CommandRuntimeException::class);
        $merger = new ExtendedMerger(['binary' => 'merge2pdf']);
        $merger->addInput('tests/resource/a.pdf', 1);
        $merger->merge('/dev/null');
    }

    public function testShouldThroughExceptionIfBinaryNotDefined()
    {
        $this->expectException(LogicException::class);
        $merger = new ExtendedMerger();
        $merger->getCommandOptions();
    }

    public function testShouldThroughExceptionIfInputsNotDefined()
    {
        $this->expectException(LogicException::class);
        $merger = new ExtendedMerger(['binary' => 'merge2pdf']);
        $merger->getCommandOptions();
    }
}
