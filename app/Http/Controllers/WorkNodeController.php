<?php

namespace App\Http\Controllers;

use App\Components\BaseResource;
use App\Services\WorkNodeService;
use Illuminate\Http\Request;
use Paganini\POJOs\Response;

class WorkNodeController extends Controller
{
    protected WorkNodeService $workNodeService;

    public function __construct(WorkNodeService $workNodeService)
    {
        $this->workNodeService = $workNodeService;
    }

    /**
     * Get all work nodes.
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        $validated = $request->validate([
            'search_name' => 'sometimes|string|max:255',
        ]);

        $workNodes = $this->workNodeService->getAllWorkNodes($validated);

        return Response::success($workNodes)->toArray();
    }

    /**
     * Get a specific work node.
     *
     * @param int $id
     * @return array
     */
    public function show(int $id): array
    {
        $workNode = $this->workNodeService->getWorkNodeById($id);

        return Response::success($workNode)->toArray();
    }

    /**
     * Create a new work node.
     *
     * @param Request $request
     * @return array
     */
    public function store(Request $request): array
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|int|in:0,1',
        ];
        $validationRules = array_merge($validationRules, BaseResource::getValidationRules());
        $validated = $request->validate($validationRules);

        $workNode = $this->workNodeService->createWorkNode($validated);

        return Response::success($workNode)->toArray();
    }

    /**
     * Update a work node.
     *
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function update(Request $request, int $id): array
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|int|in:0,1',
        ];
        if ($request->has('resources')) {
            $validationRules = array_merge($validationRules, BaseResource::getValidationRules());
        }
        $validated = $request->validate($validationRules);

        $workNode = $this->workNodeService->updateWorkNode($id, $validated);

        return Response::success($workNode)->toArray();
    }

    /**
     * Delete a work node.
     *
     * @param int $id
     * @return array
     */
    public function destroy(int $id): array
    {
        $this->workNodeService->deleteWorkNode($id);

        return Response::success()->toArray();
    }


    public function getResources(int $id): array
    {
        $workNode = $this->workNodeService->getWorkNodeById($id);

        $resources = $workNode->getResources();
        foreach ($resources as $resource) {
            $resource->handle();
        }

        return Response::success($workNode)->toArray();
    }
}

