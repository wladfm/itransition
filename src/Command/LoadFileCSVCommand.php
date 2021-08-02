<?php
namespace App\Command;

use App\Controller\LoadFileCSVController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LoadFileCSVCommand extends Command
{
    protected static $defaultName = 'app:load-csv';
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator) {
        $this->entityManager = $em;
        $this->validator = $validator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('pathCSV', InputArgument::REQUIRED, 'The path to the csv-file.')
            ->addArgument('noFirstLine', InputArgument::OPTIONAL, 'Don\'t process the first line.', false)
            ->addArgument('test', InputArgument::OPTIONAL, 'Test script.', false)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loadFileCSVController = new LoadFileCSVController($this->entityManager, $this->validator);
        // Add path file for parse
        $loadFileCSVController->setPath($input->getArgument('pathCSV'));
        // Process the first line or not
        $loadFileCSVController->setNoFirstLine(boolval($input->getArgument('noFirstLine')));
        // Test script
        $loadFileCSVController->setTestScript(boolval($input->getArgument('noFirstLine')));
        // Parse file
        $msg = $loadFileCSVController->parse();
        // If there are script errors
        if($msg != '') {
            $output->writeln('ERROR. ' . $msg);
        } else {
            // Display the result
            $output->writeln('Confirmed: ' . $loadFileCSVController->getCountConfirmed());
            $output->writeln('Not confirmed: ' . $loadFileCSVController->getCountNotConfirmed());
            // Displaying data errors
            if(count($loadFileCSVController->getErrors()) > 0) {
                $output->writeln('Report errors:');
                foreach ($loadFileCSVController->getErrors() as $error) {
                    $output->writeln($error);
                }
            }
        }
        return 1;
    }
}