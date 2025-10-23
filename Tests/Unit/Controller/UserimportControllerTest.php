<?php

namespace Visol\Userimport\Tests\Unit\Controller;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Visol\Userimport\Controller\UserimportController;

/**
 * Test case.
 */
class UserimportControllerTest extends UnitTestCase
{
    /**
     * @var UserimportController
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(UserimportController::class)
            ->setMethods(['redirect', 'forward', 'addFlashMessage'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
