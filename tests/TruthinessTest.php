<?php

declare(strict_types=1);

namespace Tests;

use Dzentota\TemplateVariable\TemplateVariable;
use PHPUnit\Framework\TestCase;

class TruthinessTest extends TestCase
{
    public function testBoolAndNullAreNotWrapped(): void
    {
        $this->assertFalse(TemplateVariable::create(false));
        $this->assertTrue(TemplateVariable::create(true));
        $this->assertNull(TemplateVariable::create(null));
    }

    public function testCollectionItemsBoolNotWrapped(): void
    {
        $c = TemplateVariable::create(['is_admin' => false, 'n' => null]);
        $this->assertFalse($c['is_admin']);
        $this->assertNull($c['n']);
    }

    public function testIsTruthy(): void
    {
        $this->assertFalse(TemplateVariable::create('')->isTruthy());
        $this->assertFalse(TemplateVariable::create('0')->isTruthy());
        $this->assertFalse(TemplateVariable::create(0)->isTruthy());
        $this->assertTrue(TemplateVariable::create('x')->isTruthy());
        $this->assertTrue(TemplateVariable::html(false)->isTruthy() === false);
        $this->assertFalse(TemplateVariable::create([])->isTruthy());
        $this->assertTrue(TemplateVariable::create([1])->isTruthy());
    }
}
