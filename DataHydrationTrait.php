<?php
namespace Rzeka\DataHandler;

trait DataHydrationTrait
{
    /**
     * @var array
     */
    private $hydrationProperties;

    /**
     * @var array
     */
    private $hydrationSetters;

    /**
     * @param array $data
     *
     * @throws \OutOfBoundsException
     */
    public function hydrate(array $data): void
    {
        if (!$data) {
            return;
        }

        if ($this->hydrationProperties === null || $this->hydrationSetters === null) {
            $reflection = new \ReflectionClass($this);
            $this->hydrationProperties = array_map(function ($v) {
                return $v->name;
            }, $reflection->getProperties(\ReflectionProperty::IS_PUBLIC));

            $this->hydrationSetters = array_filter(array_map(function ($v) {
                return strpos($v->name, 'set') === 0 ? $v->name : null;
            }, $reflection->getMethods(\ReflectionMethod::IS_PUBLIC)));
        }

        $camelCaseConvert = function ($v) {
            if (strpos($v, '_') === false) {
                return $v;
            }

            $v = explode('_', $v);

            $first = array_shift($v);
            $v = array_map('ucfirst', $v);

            return $first . implode('', $v);
        };

        foreach ($data as $key => $value) {
            if (in_array($key, $this->hydrationProperties, true)) {
                $this->{$key} = $value;
            } else {
                $camelCaseKey = $camelCaseConvert($key);
                $setter = 'set' . ucfirst($camelCaseKey);

                if ($camelCaseKey !== $key && in_array($camelCaseKey, $this->hydrationProperties, true)) {
                    $this->{$camelCaseKey} = $value;
                } elseif (in_array($setter, $this->hydrationSetters, true)) {
                    $this->$setter($value);
                } else {
                    throw new \OutOfBoundsException(sprintf(
                        'Invalid attribute provided "%s"',
                        $key
                    ));
                }
            }
        }
    }
}
