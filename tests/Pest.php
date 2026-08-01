<?php

use NewDebugBar\Tests\ProductionTestCase;
use NewDebugBar\Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(ProductionTestCase::class)->in('Production');
