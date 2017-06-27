<?php
namespace Rzeka\DataHandler;

interface DataHydratableInterface
{
    /**
     * @param array $data
     */
    public function hydrate(array $data): void;
}
