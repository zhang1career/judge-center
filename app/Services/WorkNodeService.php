<?php

namespace App\Services;

use App\Constants\ActionTypeContent;
use App\Models\WorkNode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class WorkNodeService
{
    /**
     * Get all work nodes with optional filters.
     *
     * @param array $filters
     * @return Collection
     */
    public function getAllWorkNodes(array $filters = []): Collection
    {
        $query = WorkNode::query();

        // Search by name if provided
        if (isset($filters['search_name'])) {
            $query->where('name', 'like', '%' . $filters['search_name'] . '%');
        }

        return $query->orderBy('id', 'desc')->get();
    }

    /**
     * Get a work node by ID.
     *
     * @param int $id
     * @return WorkNode|null
     */
    public function getWorkNodeById(int $id): ?WorkNode
    {
        return WorkNode::find($id);
    }

    /**
     * Create a new work node.
     *
     * @param array $data
     * @return WorkNode
     */
    public function createWorkNode(array $data): WorkNode
    {
        $currentTime = (int)(now()->timestamp * 1000);

        return DB::transaction(function () use ($data, $currentTime) {
            return WorkNode::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'resources' => $data['resources'] ?? [],
                'actions' => [ActionTypeContent::REJECT, ActionTypeContent::APPROVE],
                'ct' => $currentTime,
                'ut' => $currentTime,
            ]);
        });
    }

    /**
     * Update a work node.
     *
     * @param int $id
     * @param array $data
     * @return WorkNode
     * @throws ModelNotFoundException
     */
    public function updateWorkNode(int $id, array $data): WorkNode
    {
        $currentTime = (int)(now()->timestamp * 1000);

        return DB::transaction(function () use ($id, $data, $currentTime) {
            $workNode = WorkNode::findOrFail($id);

            $updateData = [];
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            if (isset($data['resources'])) {
                $updateData['resources'] = $data['resources'];
            }

            if (!empty($updateData)) {
                $updateData['ut'] = $currentTime;
                $workNode->update($updateData);
            }

            return $workNode->fresh();
        });
    }

    /**
     * Delete a work node.
     *
     * @param int $id
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deleteWorkNode(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $workNode = WorkNode::findOrFail($id);
            return $workNode->delete();
        });
    }
}

