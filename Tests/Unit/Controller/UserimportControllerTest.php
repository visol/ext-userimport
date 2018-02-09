<?php
namespace Visol\Userimport\Tests\Unit\Controller;

/**
 * Test case.
 *
 * @author Lorenz Ulrich <lorenz.ulrich@visol.ch>
 */
class UserimportControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \Visol\Userimport\Controller\UserimportController
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(\Visol\Userimport\Controller\UserimportController::class)
            ->setMethods(['redirect', 'forward', 'addFlashMessage'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

}
