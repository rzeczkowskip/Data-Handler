<?php
namespace Rzeka\DataHandler\Tests;

use PHPUnit\Framework\TestCase;
use Rzeka\DataHandler\DataHydratableInterface;
use Rzeka\DataHandler\DataHandler;
use Rzeka\DataHandler\DataHandlerResult;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataHandlerTest extends TestCase
{
    /**
     * @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validator;

    public function setUp()
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
    }

    public function tearDown()
    {
        $this->validator = null;
    }

    public function testHandleWithDataHydration()
    {
        $requestData = ['test'];

        $data = $this->createMock(DataHydratableInterface::class);
        $data
            ->expects(static::once())
            ->method('hydrate')
            ->with($requestData);

        $this->validator
            ->expects(static::once())
            ->method('validate')
            ->with($data, null, null)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $handler = new DataHandler($this->validator);
        $result = $handler->handle($requestData, $data);

        static::assertInstanceOf(DataHandlerResult::class, $result);
    }

    public function testHandleWithAdditionalConstraints()
    {
        $data = [];

        $constraints = [
            $this->createMock(Constraint::class)
        ];

        $violationList1 = $this->createMock(ConstraintViolationListInterface::class);
        $violationList2 = $this->createMock(ConstraintViolationListInterface::class);

        $this->validator
            ->expects(static::exactly(2))
            ->method('validate')
            ->withConsecutive(
                [$data, null, null],
                [$data, $constraints, null]
            )
            ->willReturnOnConsecutiveCalls(
                $violationList1,
                $violationList2
            );

        $violationList1
            ->expects(static::once())
            ->method('addAll')
            ->with($violationList2);

        $handler = new DataHandler($this->validator);
        $handler->handle(
            [],
            $data,
            [
                'constraints_extra' => $constraints
            ]
        );
    }

    public function testHandleWithValidationGroups()
    {
        $options = [
            'validation_groups' => ['test']
        ];

        $data = [];

        $this->validator
            ->expects(static::once())
            ->method('validate')
            ->with($data, null, $options['validation_groups'])
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $handler = new DataHandler($this->validator);
        $handler->handle([], $data, $options);
    }

    public function testHandleWithInvalidOptions()
    {
        $options = [
            'invalid' => true
        ];

        $this->expectException(ExceptionInterface::class);

        $handler = new DataHandler($this->validator);
        $handler->handle([], [], $options);
    }
}
