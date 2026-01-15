<?php

namespace App\Services;

use App\Constants\ActionTypeContent;
use App\Constants\WorkNodeTypeConstant;
use App\Models\WorkNode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
        // Validations
        // CONTROL node should not have resources of DATA type and vice versa
        if (isset($data['type']) && isset($data['resources']) && is_array($data['resources'])) {
            foreach ($data['resources'] as $resource) {
                if ($data['type'] === WorkNodeTypeConstant::TYPE_DATA && $resource['type'] !== WorkNodeTypeConstant::TYPE_DATA) {
                    throw new InvalidArgumentException('DATA type work node cannot have CONTROL type resources.');
                }
                if ($data['type'] === WorkNodeTypeConstant::TYPE_CONTROL && $resource['type'] !== WorkNodeTypeConstant::TYPE_CONTROL) {
                    throw new InvalidArgumentException('CONTROL type work node cannot have DATA type resources.');
                }
            }
        }

        $currentTime = (int)(now()->timestamp * 1000);
        return DB::transaction(function () use ($data, $currentTime) {
            return WorkNode::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? WorkNodeTypeConstant::TYPE_DATA,
                'resources' => $data['resources'] ?? [],
                'actions' => [ActionTypeContent::REJECT, ActionTypeContent::APPROVE, ActionTypeContent::PUSHBACK],
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
        // Validations
        // CONTROL node should not have resources of DATA type and vice versa
        if (isset($data['type']) && isset($data['resources']) && is_array($data['resources'])) {
            foreach ($data['resources'] as $resource) {
                if ($data['type'] === WorkNodeTypeConstant::TYPE_DATA && $resource['type'] !== WorkNodeTypeConstant::TYPE_DATA) {
                    throw new InvalidArgumentException('DATA type work node cannot have CONTROL type resources.');
                }
                if ($data['type'] === WorkNodeTypeConstant::TYPE_CONTROL && $resource['type'] !== WorkNodeTypeConstant::TYPE_CONTROL) {
                    throw new InvalidArgumentException('CONTROL type work node cannot have DATA type resources.');
                }
            }
        }

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
            if (isset($data['type'])) {
                $updateData['type'] = $data['type'];
            }
            if (isset($data['resources'])) {
                $updateData['resources'] = $data['resources'];
            }
            if (isset($data['actions'])) {
                $updateData['actions'] = $data['actions'];
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

