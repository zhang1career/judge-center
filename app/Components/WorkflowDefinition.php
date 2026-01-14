<?php

namespace App\Components;

use InvalidArgumentException;

/**
 * WorkflowDefinition
 *
 * Defines the JSON structure for workflow definition.
 * Currently, supports sequential execution, with extensibility for future execution patterns.
 *
 * Current structure:
 * {
 *   "nodes": [1, 2, 3]
 * }
 */
class WorkflowDefinition
{
    private array $nodes;

    /**
     * @param array $nodes Array of node IDs for sequential execution
     */
    public function __construct(array $nodes)
    {
        $this->nodes = $nodes;

        $this->validate();
    }

    /**
     * Create a WorkflowDefinition from array data.
     *
     * @param array $data
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['nodes'])) {
            throw new InvalidArgumentException('Workflow definition must include "nodes" field');
        }

        return new self(
            $data['nodes']
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'nodes' => $this->nodes,
        ];
    }

    /**
     * Validate the definition structure.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        // Validate nodes
        if (empty($this->nodes)) {
            throw new InvalidArgumentException('Workflow definition must include at least one node');
        }

        foreach ($this->nodes as $nodeId) {
            if (!is_int($nodeId) || $nodeId <= 0) {
                throw new InvalidArgumentException(
                    sprintf('Invalid node ID: %s. Node IDs must be positive integers.',
                        is_scalar($nodeId) ? $nodeId : gettype($nodeId)
                    )
                );
            }
        }

        // Check for duplicate node IDs
        if (count($this->nodes) !== count(array_unique($this->nodes))) {
            throw new InvalidArgumentException('Workflow definition contains duplicate node IDs');
        }
    }

    /**
     * Get validation rules for Laravel request validation.
     *
     * @return array
     */
    public static function getValidationRules(): array
    {
        return [
            'definition' => 'required|array',
            'definition.nodes' => 'required|array|min:1',
            'definition.nodes.*' => 'required|integer|min:1',
        ];
    }

    /**
     * Get the array of node IDs.
     *
     * @return array
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }
}

