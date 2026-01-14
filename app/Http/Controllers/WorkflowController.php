<?php

namespace App\Http\Controllers;

use App\Components\WorkflowDefinition;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Paganini\POJOs\Response;

class WorkflowController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Get all workflows.
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        $validated = $request->validate([
            'search_name' => 'sometimes|string|max:255',
            'status' => 'sometimes|integer',
        ]);

        $workflows = $this->workflowService->getAllWorkflows($validated);

        return Response::success($workflows)->toArray();
    }

    /**
     * Get a specific workflow.
     *
     * @param int $id
     * @return array
     */
    public function show(int $id): array
    {
        $workflow = $this->workflowService->getWorkflowById($id);

        if (!$workflow) {
            return Response::error('Workflow not found', 404)->toArray();
        }

        return Response::success($workflow)->toArray();
    }

    /**
     * Create a new workflow.
     *
     * @param Request $request
     * @return array
     */
    public function store(Request $request): array
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|integer',
            'stage' => 'sometimes|integer|min:0',
        ];
        $validationRules = array_merge($validationRules, WorkflowDefinition::getValidationRules());
        $validated = $request->validate($validationRules);

        $workflow = $this->workflowService->createWorkflow($validated);

        return Response::success($workflow)->toArray();
    }

    /**
     * Update a workflow.
     *
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function update(Request $request, int $id): array
    {
        $validationRules = [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|integer',
            'stage' => 'sometimes|integer|min:0',
        ];
        if ($request->has('definition')) {
            $validationRules = array_merge($validationRules, WorkflowDefinition::getValidationRules());
        }
        $validated = $request->validate($validationRules);

        $workflow = $this->workflowService->updateWorkflow($id, $validated);

        return Response::success($workflow)->toArray();
    }

    /**
     * Delete a workflow.
     *
     * @param int $id
     * @return array
     */
    public function destroy(int $id): array
    {
        $this->workflowService->deleteWorkflow($id);

        return Response::success()->toArray();
    }
}
