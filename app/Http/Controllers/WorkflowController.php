<?php

namespace App\Http\Controllers;

use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string',
            'search' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $workflows = $this->workflowService->getAllWorkflows($validator->validated());
            return response()->json([
                'success' => true,
                'data' => $workflows,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve workflows',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific workflow.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $workflow = $this->workflowService->getWorkflowById($id);
            
            if (!$workflow) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workflow not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $workflow,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve workflow',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new workflow.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'definition' => 'nullable|array',
            'status' => 'sometimes|string|in:draft,active,inactive',
            'created_by' => 'nullable|integer',
            'updated_by' => 'nullable|integer',
            'nodes' => 'nullable|array',
            'nodes.*.name' => 'required_with:nodes|string|max:255',
            'nodes.*.node_type' => 'nullable|string',
            'nodes.*.order' => 'nullable|integer',
            'nodes.*.config' => 'nullable|array',
            'nodes.*.resources' => 'nullable|array',
            'nodes.*.actions' => 'nullable|array',
            'nodes.*.permissions' => 'nullable|array',
            'nodes.*.permissions.*.permission_type' => 'required_with:nodes.*.permissions|string|in:visibility,operability',
            'nodes.*.permissions.*.target_type' => 'required_with:nodes.*.permissions|string|in:role,user',
            'nodes.*.permissions.*.target_id' => 'required_with:nodes.*.permissions|integer',
            'nodes.*.resource_list' => 'nullable|array',
            'nodes.*.resource_list.*.resource_type' => 'required_with:nodes.*.resource_list|string|in:text,image,table,file',
            'nodes.*.resource_list.*.name' => 'nullable|string',
            'nodes.*.resource_list.*.oss_key' => 'nullable|string',
            'nodes.*.resource_list.*.oss_bucket' => 'nullable|string',
            'nodes.*.resource_list.*.metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $workflow = $this->workflowService->createWorkflow($validator->validated());
            return response()->json([
                'success' => true,
                'message' => 'Workflow created successfully',
                'data' => $workflow,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create workflow',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a workflow.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'definition' => 'nullable|array',
            'status' => 'sometimes|string|in:draft,active,inactive',
            'updated_by' => 'nullable|integer',
            'nodes' => 'nullable|array',
            'nodes.*.id' => 'nullable|integer',
            'nodes.*.name' => 'required_with:nodes|string|max:255',
            'nodes.*.node_type' => 'nullable|string',
            'nodes.*.order' => 'nullable|integer',
            'nodes.*.config' => 'nullable|array',
            'nodes.*.resources' => 'nullable|array',
            'nodes.*.actions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $workflow = $this->workflowService->updateWorkflow($id, $validator->validated());
            return response()->json([
                'success' => true,
                'message' => 'Workflow updated successfully',
                'data' => $workflow,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Workflow not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update workflow',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a workflow.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->workflowService->deleteWorkflow($id);
            
            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Workflow deleted successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete workflow',
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Workflow not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete workflow',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a node to a workflow.
     *
     * @param Request $request
     * @param int $workflowId
     * @return JsonResponse
     */
    public function addNode(Request $request, int $workflowId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'node_type' => 'nullable|string',
            'order' => 'nullable|integer',
            'config' => 'nullable|array',
            'resources' => 'nullable|array',
            'actions' => 'nullable|array',
            'permissions' => 'nullable|array',
            'permissions.*.permission_type' => 'required_with:permissions|string|in:visibility,operability',
            'permissions.*.target_type' => 'required_with:permissions|string|in:role,user',
            'permissions.*.target_id' => 'required_with:permissions|integer',
            'resource_list' => 'nullable|array',
            'resource_list.*.resource_type' => 'required_with:resource_list|string|in:text,image,table,file',
            'resource_list.*.name' => 'nullable|string',
            'resource_list.*.oss_key' => 'nullable|string',
            'resource_list.*.oss_bucket' => 'nullable|string',
            'resource_list.*.metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $node = $this->workflowService->addNodeToWorkflow($workflowId, $validator->validated());
            return response()->json([
                'success' => true,
                'message' => 'Node added successfully',
                'data' => $node,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add node',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
