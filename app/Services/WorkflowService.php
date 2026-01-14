<?php

namespace App\Services;

use App\Components\WorkflowDefinition;
use App\Constants\WorkflowStatusConstant;
use App\Models\Workflow;
use App\Models\WorkNode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WorkflowService
{

    private WorkNodeService $workNodeService;

    public function __construct(WorkNodeService $workNodeService)
    {
        $this->workNodeService = $workNodeService;
    }


    /**
     * Get all workflows with optional filters.
     *
     * @param array $filters
     * @return Collection
     */
    public function getAllWorkflows(array $filters = []): Collection
    {
        $query = Workflow::query();

        // Search by name if provided
        if (isset($filters['search_name'])) {
            $query->where('name', 'like', '%' . $filters['search_name'] . '%');
        }

        // Filter by status if provided
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('id', 'desc')->get();
    }

    /**
     * Get a workflow by ID.
     *
     * @param int $id
     * @return Workflow|null
     */
    public function getWorkflowById(int $id): ?Workflow
    {
        return Workflow::find($id);
    }

    /**
     * Create a new workflow.
     *
     * @param array $data
     * @return Workflow
     * @throws InvalidArgumentException
     */
    public function createWorkflow(array $data): Workflow
    {
        $currentTime = (int)(now()->timestamp * 1000);

        return DB::transaction(function () use ($data, $currentTime) {
            // Validate and parse definition
            $definition = $this->parseDefinition($data['definition'] ?? []);

            // Extract node IDs from definition for the nodes field
            $nodeIds = $definition->getNodes();
            $nodes = implode(',', $nodeIds);

            return Workflow::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'definition' => $definition->toArray(),
                'nodes' => $nodes,
                'status' => $data['status'] ?? WorkflowStatusConstant::DRAFT,
                'stage' => $data['stage'] ?? 0,
                'ct' => $currentTime,
                'ut' => $currentTime,
            ]);
        });
    }

    /**
     * Update a workflow.
     *
     * @param int $id
     * @param array $data
     * @return Workflow
     * @throws ModelNotFoundException
     * @throws InvalidArgumentException
     */
    public function updateWorkflow(int $id, array $data): Workflow
    {
        $currentTime = (int)(now()->timestamp * 1000);

        return DB::transaction(function () use ($id, $data, $currentTime) {
            $workflow = Workflow::findOrFail($id);

            $updateData = [];
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }
            if (isset($data['stage'])) {
                $updateData['stage'] = $data['stage'];
            }
            if (isset($data['definition'])) {
                // Validate and parse definition
                $definition = $this->parseDefinition($data['definition']);
                $updateData['definition'] = $definition->toArray();

                // Update nodes field
                $nodeIds = $definition->getNodes();
                $updateData['nodes'] = implode(',', $nodeIds);
            }

            if (!empty($updateData)) {
                $updateData['ut'] = $currentTime;
                $workflow->update($updateData);
            }

            return $workflow->fresh();
        });
    }

    /**
     * Delete a workflow.
     *
     * @param int $id
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deleteWorkflow(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $workflow = Workflow::findOrFail($id);
            return $workflow->delete();
        });
    }

    /**
     * Get the current work node based on workflow stage.
     *
     * @param int $workflowId
     * @return WorkNode|null
     * @throws ModelNotFoundException
     */
    public function getCurrentWorkNode(int $workflowId): ?WorkNode
    {
        $workflow = Workflow::findOrFail($workflowId);

        // Get workflow definition
        $definition = WorkflowDefinition::fromArray($workflow->definition);
        $nodes = $definition->getNodes();

        // Get current stage (node index)
        $currentIndex = $workflow->stage ?? 0;

        // Check if current index is valid
        if ($currentIndex < 0 || $currentIndex >= count($nodes)) {
            return null;
        }

        // Get the node ID at current index
        $nodeId = $nodes[$currentIndex];

        // Get the work node by ID
        return $this->workNodeService->getWorkNodeById($nodeId);
    }

    /**
     * Parse and validate workflow definition.
     *
     * @param array $definitionData
     * @return WorkflowDefinition
     * @throws InvalidArgumentException
     */
    private function parseDefinition(array $definitionData): WorkflowDefinition
    {
        try {
            return WorkflowDefinition::fromArray($definitionData);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException('Invalid workflow definition: ' . $e->getMessage(), 0, $e);
        }
    }
}

