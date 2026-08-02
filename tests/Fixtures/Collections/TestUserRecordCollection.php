<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\LaravelImages\Tests\Fixtures\Records\TestUserRecord;

final class TestUserRecordCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TestUserRecord::class);
    }
}
