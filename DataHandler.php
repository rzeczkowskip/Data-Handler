<?php
namespace Rzeka\DataHandler;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataHandler
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param array $requestData
     * @param $data
     * @param array $options
     *
     * @return DataHandlerResult
     */
    public function handle(array $requestData, $data, array $options = []): DataHandlerResult
    {
        if ($data instanceof DataHydratableInterface) {
            $data->hydrate($requestData);
        }

        $options = $this->resolveOptions($options);
        $violationList = $this->validator->validate($data, $options['constraints'], $options['validation_groups']);

        return new DataHandlerResult($data, $requestData, $violationList);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function resolveOptions(array $options)
    {
        if (!$this->optionsResolver) {
            $this->optionsResolver = new OptionsResolver();

            $this->optionsResolver->setDefaults([
                'validation_groups' => null,
                'constraints' => null
            ]);
        }

        return $this->optionsResolver->resolve($options);
    }
}
