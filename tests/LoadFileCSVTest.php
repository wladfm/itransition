<?php

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LoadFileCSVTest extends KernelTestCase
{
    // Test run script
    public function testExecute()
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('app:load-csv');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'pathCSV' => __DIR__ . '/stock.csv',
            'noFirstLine' => true,
            'test' => true
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Test loader csv-file', $output);
    }
}