<?php
namespace Rzeka\DataHandler\Tests;

use PHPUnit\Framework\TestCase;
use Rzeka\DataHandler\DataHandlerResult;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class DataHandlerResultTest extends TestCase
{
    /**
     * @var ConstraintViolationList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $violationList;

    public function setUp()
    {
        $this->violationList = $this->createMock(ConstraintViolationList::class);
    }

    public function tearDown()
    {
        $this->violationList = null;
    }

    /**
     * @param $errorCount
     * @param $expected
     *
     * @dataProvider isValidProvider
     */
    public function testIsValid($errorCount, $expected)
    {
        $this->violationList
            ->expects(static::once())
            ->method('count')
            ->willReturn($errorCount);

        $handlerResult = new DataHandlerResult(null, [], $this->violationList);
        $result = $handlerResult->isValid();

        static::assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function isValidProvider()
    {
        return [
            [0, true],
            [1, false]
        ];
    }

    public function testGetErrorsNoErrors()
    {
        $this->violationList
            ->expects(static::once())
            ->method('count')
            ->willReturn(0);

        $this->violationList
            ->expects(static::never())
            ->method('getIterator');

        $handlerResult = new DataHandlerResult(null, [], $this->violationList);
        $result = $handlerResult->getErrors();

        static::assertEquals([], $result);
    }

    public function testGetErrors()
    {
        $expected = [
            [
                'path' => '-',
                'message' => 'Global error',
                'error' => 'ERR_CODE_GLOBAL',
                'parameters' => []
            ],
            [
                'path' => 'test',
                'message' => 'Test error',
                'error' => 'ERR_CODE_TEST',
                'parameters' => []
            ],
            [
                'path' => 'multiple',
                'message' => 'Multiple error messages',
                'error' => 'ERR_CODE_MULTIPLE_1',
                'parameters' => []
            ],
            [
                'path' => 'multiple',
                'message' => 'on same path',
                'error' => null,
                'parameters' => [
                    'test' => 'param'
                ]
            ],
        ];
        $violations = $this->getViolations($expected);

        $this->violationList
            ->expects(static::once())
            ->method('count')
            ->willReturn(1);

        $this->violationList
            ->expects(static::once())
            ->method('getIterator')
            ->willReturn($violations);

        $handlerResult = new DataHandlerResult(null, [], $this->violationList);
        $result = $handlerResult->getErrors();

        static::assertEquals($expected, $result);
    }

    public function testGetRequestData()
    {
        $requestData = [];

        $handlerResult = new DataHandlerResult(null, [], $this->violationList);
        $result = $handlerResult->getRequestData();

        static::assertEquals($requestData, $result);
    }

    public function testGetViolationList()
    {
        $handlerResult = new DataHandlerResult(null, [], $this->violationList);
        $result = $handlerResult->getViolationList();

        static::assertEquals($this->violationList, $result);
    }

    public function testGetData()
    {
        $data = new class {};

        $handlerResult = new DataHandlerResult($data, [], $this->violationList);
        $result = $handlerResult->getData();

        static::assertEquals($data, $result);
    }

    /**
     * @param array $errors
     *
     * @return \ArrayIterator
     */
    private function getViolations(array $errors)
    {
        $violations = [];
        $constraint = new class extends Constraint {
            public static function getErrorName($errorCode)
            {
                if ($errorCode === null) {
                    throw new InvalidArgumentException();
                }

                return $errorCode;
            }
        };

        foreach ($errors as $i => $error) {
            $violationConstraint = clone $constraint;

            $path = $error['path'];
            if ($path === '-') {
                $path = null;
            }

            if ($i === 0) {
                $violationConstraint->payload = [
                    'error' => $error['error']
                ];
            }

            $violation = $this->createMock(ConstraintViolation::class);
            $violation
                ->expects(static::once())
                ->method('getPropertyPath')
                ->willReturn($path);

            $violation
                ->expects(static::once())
                ->method('getConstraint')
                ->willReturn($violationConstraint);

            $violation
                ->expects(static::once())
                ->method('getMessage')
                ->willReturn($error['message']);

            if ($i !== 0) {
                $violation
                    ->expects(static::once())
                    ->method('getCode')
                    ->willReturn($error['error']);
            }

            $parameters = null;
            if ($error['parameters']) {
                $keys = array_map(function ($v) {
                    return '{{ ' . $v . ' }}';
                }, array_keys($error['parameters']));

                $parameters = array_combine($keys, $error['parameters']);
            }
            $violation
                ->expects(static::atLeastOnce())
                ->method('getParameters')
                ->willReturn($parameters);

            $violations[] = $violation;
        }

        return new \ArrayIterator($violations);
    }
}
