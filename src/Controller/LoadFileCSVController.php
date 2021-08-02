<?php

namespace App\Controller;

use App\Entity\Tblproductdata;
use AppBundle\Validation\LoadFileCSVValidation;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use PDOException;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LoadFileCSVController extends AbstractController
{
    /**
     * Manager Entity
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;
    /**
     * Interface for Validator
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;
    /**
     * The path to the csv-file
     * @var string
     */
    private string $path;
    /**
     * Process the first line or not
     * @var bool
     */
    private bool $noFirstLine;
    /**
     * Test script
     * @var bool
     */
    private bool $testScript;
    /**
     * List of lucky lines
     * @var array
     */
    private array $confirmed;
    /**
     * List of failed lines
     * @var array
     */
    private array $not_confirmed;
    /**
     * Error list
     * @var array
     */
    private array $errors;

    /**
     * LoadFileCSVController constructor.
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     */
    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $this->entityManager = $em;
        $this->validator = $validator;
        $this->path = '';
        $this->noFirstLine = false;
        $this->testScript = false;
        $this->confirmed = [];
        $this->not_confirmed = [];
        $this->errors = [];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @param bool $noFirstLine
     */
    public function setNoFirstLine(bool $noFirstLine): void
    {
        $this->noFirstLine = $noFirstLine;
    }

    /**
     * @param bool $testScript
     */
    public function setTestScript(bool $testScript): void
    {
        $this->testScript = $testScript;
    }

    /**
     * @param array $confirmed
     */
    private function setConfirmed(array $confirmed): void
    {
        if(!is_array($this->confirmed))
            $this->confirmed = [];
        $this->confirmed[] = $confirmed;
    }

    /**
     * @return int
     */
    public function getCountConfirmed(): int
    {
        return is_array($this->confirmed) ? count($this->confirmed) : 0;
    }

    /**
     * @return int
     */
    public function getCountNotConfirmed(): int
    {
        return is_array($this->not_confirmed) ? count($this->not_confirmed) : 0;
    }

    /**
     * @param array $not_confirmed
     */
    private function setNotConfirmed(array $not_confirmed): void
    {
        if(!is_array($this->not_confirmed))
            $this->not_confirmed = [];
        $this->not_confirmed[] = $not_confirmed;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param string $err
     */
    private function setError(string $err): void
    {
        if(!is_array($this->errors))
            $this->errors = [];
        $this->errors[] = $err;
    }

    /**
     * Parse file
     * @return string
     * @throws \Exception
     */
    public function parse(): string
    {
        $msg = '';
        do {
            // Checked file
            if(($msg = $this->checkFile()) != '') {
                break;
            }
            if (($handle = fopen($this->path, "r")) !== FALSE) {
                $index_line = 0;
                // We read the lines
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $index_line++;
                    // The first line is not processed if $this->noFirstLine == true
                    if($index_line == 1 && $this->noFirstLine)
                        continue;
                    // Checked row
                    if(($err = $this->checkData($data)) != '') {
                        $this->setError('Line ' . $index_line . '. ' . $err);
                        $this->setNotConfirmed($data);
                        continue;
                    }
                    // Convert to DB
                    if(($err = $this->toData($data)) != '') {
                        $this->setError('Line ' . $index_line . '. ' . $err);
                        $this->setNotConfirmed($data);
                        continue;
                    }
                    $this->setConfirmed($data);
                }
            } else {
                $msg = 'Unable to open file `' . $this->path . '`.';
                break;
            }
        } while(false);
        return $msg;
    }

    /**
     * Checked file
     * @return string
     */
    private function checkFile(): string
    {
        $msg = '';
        do {
            // File does not exist
            if(!file_exists($this->path)) {
                $msg = 'The specified file `' . $this->path . '` does not exist.';
                break;
            }
            // Invalid file type
            $info = new SplFileInfo($this->path);
            if($info->getExtension() != 'csv') {
                $msg = 'Invalid file `' . $this->path . '` extension.';
                break;
            }
            // Checked encoding
            $str_file = file_get_contents($this->path);
            if(mb_detect_encoding($str_file) != 'UTF-8') {
                $msg = 'Invalid file encoding.';
                break;
            }
        } while(false);
        return $msg;
    }

    /**
     * Convert to DB
     * @param $data
     * @return string
     * @throws \Exception
     */
    private function toData($data): string
    {
        $msg = '';

        do {
            // Create ORM
            $tblProductData = new Tblproductdata();
            $tblProductData->setStrproductcode(strval($data[0]));
            $tblProductData->setStrproductname(strval($data[1]));
            $tblProductData->setStrproductdesc(strval($data[2]));
            $tblProductData->setIStock(intval($data[3]));
            $tblProductData->setDCost(floatval($data[4]));
            $tblProductData->setDtmadded(new \DateTime());
            $tblProductData->setStmtimestamp(new \DateTimeImmutable());
            if(isset($data[5]) && $data[5] == 'yes') {
                $tblProductData->setDtmdiscontinued(new \DateTime());
            }

            // Valid object
            $errors = $this->validator->validate($tblProductData);
            if (count($errors) > 0) {
                $msg = implode('; ', $errors);
                break;
            }
            try {
                // If the entity manager is closed, reopen it
                if (!$this->entityManager->isOpen()) {
                    $this->entityManager = $this->entityManager->create(
                        $this->entityManager->getConnection(),
                        $this->entityManager->getConfiguration()
                    );
                }
                // Save ORM
                if(!$this->testScript) {
                    $this->entityManager->persist($tblProductData);
                    $this->entityManager->flush();
                }
            } catch (DBALException $e) {
                $msg = $e->getCode() . '. Error: ' . $e->getMessage();
            } catch (PDOException $e) {
                $msg = $e->getCode() . '. Error: ' . $e->getMessage();
            } catch (ORMException $e) {
                $msg = $e->getCode() . '. Error: ' . $e->getMessage();
            } catch (Exception $e) {
                $msg = $e->getCode() . '. Error: ' . $e->getMessage();
            }
        } while(false);

        return $msg;
    }

    /**
     * Checked row
     * @param array $data
     * @return string
     */
    private function checkData($data): string
    {
        $msg = '';

        do {
            $errors = (new LoadFileCSVValidation())->validate($data);
            if (!empty($errors)) {
                $msg = implode('; ', $errors);
                break;
            }
        } while(false);

        return $msg;
    }
}
