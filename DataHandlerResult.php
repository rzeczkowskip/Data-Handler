<?php
namespace Rzeka\DataHandler;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class DataHandlerResult
{
    /**
     * @var array
     */
    private $requestData;

    /**
     * @var ConstraintViolationListInterface
     */
    private $violationList;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param mixed $data
     * @param array $requestData
     * @param ConstraintViolationListInterface $violationList
     */
    public function __construct($data, array $requestData, ConstraintViolationListInterface $violationList)
    {
        $this->data = $data;
        $this->requestData = $requestData;
        $this->violationList = $violationList;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->violationList->count() === 0;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        if ($this->errors === null) {
            if ($this->isValid()) {
                $this->errors = [];
            } else {
                $this->buildErrors();
            }
        }

        return $this->errors;
    }

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getViolationList(): ConstraintViolationListInterface
    {
        return $this->violationList;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    private function buildErrors(): void
    {
        $errors = [];

        /** @var ConstraintViolation $violation */
        foreach ($this->violationList as $violation) {
            $path = $violation->getPropertyPath();
            if ($path === null) {
                $path = '-';
            }

            $constraint = $violation->getConstraint();
            if (isset($constraint->payload['error'])) {
                $errorCode = $constraint->payload['error'];
            } else {
                try {
                    $errorCode = $constraint::getErrorName($violation->getCode());
                } catch (InvalidArgumentException $e) {
                    $errorCode = null;
                }
            }

            $error = [
                'path' => $path,
                'message' => $violation->getMessage(),
                'error' => $errorCode,
                'parameters' => []
            ];

            if ($violation->getParameters()) {
                $keys = array_map(function ($v) {
                    return substr($v, 3, -3);
                }, array_keys($violation->getParameters()));


                $error['parameters'] = array_combine($keys, $violation->getParameters());
            }

            $errors[] = $error;
        }

        $this->errors = $errors;
    }
}
