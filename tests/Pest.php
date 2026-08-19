<?php

use NewDebugBar\Tests\ProductionTestCase;
use NewDebugBar\Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Browser');
uses(ProductionTestCase::class)->in('Production');
