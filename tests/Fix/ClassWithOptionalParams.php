<?php

declare(strict_types=1);

namespace IW\Fix;

class ClassWithOptionalParams
{
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function __construct(
        public Fourth|null $fourth = null,
        public string $string = 'default string',
        public $options = [],
        public Zero|null $zero = null,
    ) {
    }
}
