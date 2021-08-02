<?php
namespace AppBundle\Validation;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractValidator
{
    /**
     *
     * @var ValidatorInterface
     */
    private $validator;

    /**
     *
     */
    public function __construct()
    {
        $this->validator = Validation::createValidator();
    }

    /**
     * Returns validation rules
     *
     * @return Collection
     */
    abstract protected function getConstraint(): Collection;

    /**
     * Data validation
     *
     * @param array $requestFields
     *
     * @return array
     */
    public function validate(array $requestFields): array
    {
        $errors = [];

        foreach ($this->validator->validate($requestFields, $this->getConstraint()) as $violation){
            $field = preg_replace(['/\]\[/', '/\[|\]/'], ['.', ''], $violation->getPropertyPath());
            $errors[$field] = $violation->getMessage();
        }

        return $errors;
    }
}