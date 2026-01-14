<?php

namespace App\Services;

use App\Models\ResourceMeta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ResourceMetaService
{
    /**
     * Get all resource metas with optional filters.
     *
     * @param array $filters
     * @return Collection
     */
    public function getAllResourceMetas(array $filters = []): Collection
    {
        $query = ResourceMeta::query();

        // Filter by code if provided
        if (isset($filters['code'])) {
            $query->where('code', $filters['code']);
        }

        // Search by name if provided
        if (isset($filters['search_name'])) {
            $query->where('name', 'like', '%' . $filters['search_name'] . '%');
        }

        return $query->orderBy('id', 'desc')->get();
    }

    /**
     * Get a resource meta by ID.
     *
     * @param int $id
     * @return ResourceMeta|null
     */
    public function getResourceMetaById(int $id): ?ResourceMeta
    {
        return ResourceMeta::find($id);
    }

    /**
     * Create a new resource meta.
     *
     * @param array $data
     * @return ResourceMeta
     */
    public function createResourceMeta(array $data): ResourceMeta
    {
        $currentTime = (int) (now()->timestamp * 1000);

        return DB::transaction(function () use ($data, $currentTime) {
            return ResourceMeta::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'code' => $data['code'] ?? '',
                'ct' => $currentTime,
                'ut' => $currentTime,
            ]);
        });
    }

    /**
     * Update a resource meta.
     *
     * @param int $id
     * @param array $data
     * @return ResourceMeta
     * @throws ModelNotFoundException
     */
    public function updateResourceMeta(int $id, array $data): ResourceMeta
    {
        $currentTime = (int) (now()->timestamp * 1000);

        return DB::transaction(function () use ($id, $data, $currentTime) {
            $resourceMeta = ResourceMeta::findOrFail($id);

            $updateData = [];
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (!empty($updateData)) {
                $updateData['ut'] = $currentTime;
                $resourceMeta->update($updateData);
            }

            return $resourceMeta->fresh();
        });
    }

    /**
     * Delete a resource meta.
     *
     * @param int $id
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deleteResourceMeta(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $resourceMeta = ResourceMeta::findOrFail($id);
            return $resourceMeta->delete();
        });
    }
}
