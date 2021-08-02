<?php


namespace AppBundle\Validation;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

class LoadFileCSVValidation extends AbstractValidator
{
    protected function getConstraint(): Collection
    {
        return new Collection([
            0 => $this->getStringRules(0),
            1 => $this->getStringRules(1),
            2 => $this->getStringRules(2),
            3 => $this->getStockRules(),
            4 => $this->getCostRules(),
            5 => $this->getStringRules(5)
        ]);
    }

    /**
     * Rules for string value
     * @param int $num_col
     * @return array
     */
    protected function getStringRules(int $num_col): array
    {
        return [
            new Assert\Regex([
                'pattern' => "/^./u",
                'message' => 'Non-string value entered in field ' . $num_col,
            ]),
        ];
    }

    /**
     * Rules for Stock
     * @return array
     */
    protected function getStockRules(): array
    {
        return [
            new Assert\Range([
                'min' => 10,
                'minMessage' => 'The quantity must be at least {{ limit }} in field `Stock`.'
            ]),
            new Assert\Regex([
                'pattern' => "/[0-9]/u",
                'message' => 'Non-numeric value entered in field `Stock`.',
            ]),
        ];
    }

    /**
     * Rules for Cost
     * @return array
     */
    protected function getCostRules(): array
    {
        return [
            new Assert\Range([
                'min' => 5,
                'max' => 1000,
                'notInRangeMessage' => 'The price must be from {{ min }} GBP to {{ max }} GBP in field `Cost in GBP`.'
            ]),
            new Assert\Regex([
                'pattern' => "/^[0-9]./u",
                'message' => 'Non-float value entered in field `Cost in GBP`.',
            ]),
        ];
    }
}