<?php

namespace App\Services;

use App\Components\WorkflowDefinition;
use App\Constants\ActionTypeContent;
use App\Constants\WorkflowResultConstant;
use App\Constants\WorkflowStatusConstant;
use App\Constants\WorkNodeTypeConstant;
use App\Models\Workflow;
use App\Models\WorkNode;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

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
                'result' => WorkflowResultConstant::DEFAULT,
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
            if (isset($data['result'])) {
                $updateData['result'] = $data['result'];
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
     * Process a workflow (approval flow operation).
     *
     * @param int $id
     * @param int $stage
     * @param int $action
     * @return Workflow
     * @throws ModelNotFoundException
     * @throws InvalidArgumentException
     */
    public function processWorkflow(int $id, int $stage, int $action): Workflow
    {
        $currentTime = (int)(now()->timestamp * 1000);

        return DB::transaction(function () use ($id, $stage, $action, $currentTime) {
            $workflow = Workflow::findOrFail($id);
            // Validate stage matches current workflow stage
            if ($workflow->stage !== $stage) {
                throw new InvalidArgumentException(
                    sprintf('Stage mismatch: provided stage %d does not match current workflow stage %d', $stage, $workflow->stage)
                );
            }

            // Get current work node
            $currentNode = $this->getCurrentWorkNode($id);
            if (!$currentNode) {
                throw new InvalidArgumentException('Current work node not found');
            }

            // Validate action is in the node's actions range
            $nodeActions = $currentNode->actions ?? [];
            if (!in_array($action, $nodeActions, true)) {
                throw new InvalidArgumentException(
                    sprintf('Action %d is not in the allowed actions for this node: %s', $action, implode(',', $nodeActions))
                );
            }

            // Determine node type (CONTROL or DATA)
            $resources = $currentNode->getResources();

            // Process based on node type
            if ($currentNode->type === WorkNodeTypeConstant::TYPE_CONTROL) {
                $this->processControlNode($workflow, $action, $resources);
            } else {
                $this->processDataNode($workflow, $action);
            }

            // Update workflow
            $workflow->ut = $currentTime;
            $workflow->save();

            return $workflow->fresh();
        });
    }

    /**
     * Process a CONTROL node.
     *
     * @param Workflow $workflow
     * @param int $action
     * @param array $resources
     * @return void
     * @throws InvalidArgumentException
     */
    private function processControlNode(Workflow $workflow, int $action, array $resources): void
    {
        if ($action === ActionTypeContent::APPROVE) {
            // Call handler method for CONTROL resources
            $handlerResult = true;
            foreach ($resources as $resource) {
                if ($resource->getType() != WorkNodeTypeConstant::TYPE_CONTROL) {
                    continue;
                }
                try {
                    $result = $resource->handle();
                    // If handler returns false, consider it as failed
                    if ($result === false) {
                        $handlerResult = false;
                        break;
                    }
                } catch (Exception $e) {
                    throw new RuntimeException(sprintf('Error executing CONTROL resource handler: %s', $e->getMessage()));
                }
            }

            // Determine next status/stage based on handler result and whether it's the last node
            if ($workflow->stage + 1 >= $workflow->length()) {
                $workflow->status = $handlerResult ? WorkflowStatusConstant::COMPLETED : WorkflowStatusConstant::FAILED;
                $workflow->result = WorkflowResultConstant::APPROVED;
                return;
            }
            $workflow->stage += 1;
            return;
        }
        if ($action === ActionTypeContent::REJECT) {
            $workflow->status = WorkflowStatusConstant::COMPLETED;
            $workflow->result = WorkflowResultConstant::REJECTED;
            return;
        }
        if ($action === ActionTypeContent::PUSHBACK) {
            $workflow->result = WorkflowResultConstant::DEFAULT;
            if ($workflow->stage <= 0) {
                $workflow->status = WorkflowStatusConstant::DRAFT;
                return;
            }
            $workflow->stage -= 1;
            return;
        }

        throw new InvalidArgumentException(
            sprintf('Invalid action %d for CONTROL node. Only APPROVE, REJECT, and PUSHBACK are allowed.', $action)
        );
    }

    /**
     * Process a DATA node.
     *
     * @param Workflow $workflow
     * @param int $action
     * @return void
     * @throws InvalidArgumentException
     */
    private function processDataNode(Workflow $workflow, int $action): void
    {
        if ($action === ActionTypeContent::APPROVE) {
            if ($workflow->stage + 1 >= $workflow->length()) {
                $workflow->status = WorkflowStatusConstant::COMPLETED;
                $workflow->result = WorkflowResultConstant::APPROVED;
                return;
            }
            $workflow->stage += 1;
            return;
        }
        if ($action === ActionTypeContent::REJECT) {
            $workflow->status = WorkflowStatusConstant::COMPLETED;
            $workflow->result = WorkflowResultConstant::REJECTED;
            return;
        }
        if ($action === ActionTypeContent::PUSHBACK) {
            $workflow->result = WorkflowResultConstant::DEFAULT;
            if ($workflow->stage <= 0) {
                $workflow->status = WorkflowStatusConstant::DRAFT;
                return;
            }
            $workflow->stage -= 1;
            return;
        }

        throw new InvalidArgumentException(
            sprintf('Invalid action %d for DATA node. Only APPROVE and PUSHBACK are allowed.', $action)
        );
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

