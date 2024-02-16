<?php

namespace App\Endpoint\Web\Filter;

use Spiral\Filters\Attribute\Input\Post;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\FilterInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Filters\Model\ShouldBeValidated;
use Spiral\Validator\FilterDefinition;

class TransactionFilter implements FilterInterface, HasFilterDefinition
{
    #[Post(key: 'valor')]
    public int $valor;

    #[Post(key: 'tipo')]
    public string $tipo;

    #[Post(key: 'descricao')]
    public string $descricao;

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(
            validationRules: [
                'valor' => [
                    'required',
                    'integer'
                ],
                'tipo' => [
                    'required',
                    [
                        'string::length', 1
                    ],
                    [
                        'in_array', ['c', 'd']
                    ]
                ],
                'descricao' => [
                    'required',
                    [
                        'string::longer', 1
                    ],
                    [
                        'string::shorter', 10
                    ]
                ]
            ]
        );
    }
}
