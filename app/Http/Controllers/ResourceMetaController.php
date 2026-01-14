<?php

namespace App\Http\Controllers;

use App\Services\ResourceMetaService;
use Illuminate\Http\Request;
use Paganini\POJOs\Response;

class ResourceMetaController extends Controller
{
    protected ResourceMetaService $resourceMetaService;

    public function __construct(ResourceMetaService $resourceMetaService)
    {
        $this->resourceMetaService = $resourceMetaService;
    }

    /**
     * Get all resource metas.
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:255',
            'search_name' => 'sometimes|string|max:255',
        ]);

        $resourceMetas = $this->resourceMetaService->getAllResourceMetas($validated);

        return Response::success($resourceMetas)->toArray();
    }

    /**
     * Get a specific resource meta.
     *
     * @param int $id
     * @return array
     */
    public function show(int $id): array
    {
        $resourceMeta = $this->resourceMetaService->getResourceMetaById($id);

        return Response::success($resourceMeta)->toArray();
    }

    /**
     * Create a new resource meta.
     *
     * @param Request $request
     * @return array
     */
    public function store(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'required|string',
        ]);

        $resourceMeta = $this->resourceMetaService->createResourceMeta($validated);

        return Response::success($resourceMeta)->toArray();
    }

    /**
     * Update a resource meta.
     *
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function update(Request $request, int $id): array
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $resourceMeta = $this->resourceMetaService->updateResourceMeta($id, $validated);

        return Response::success($resourceMeta)->toArray();
    }

    /**
     * Delete a resource meta.
     *
     * @param int $id
     * @return array
     */
    public function destroy(int $id): array
    {
        $this->resourceMetaService->deleteResourceMeta($id);

        return Response::success()->toArray();
    }
}
