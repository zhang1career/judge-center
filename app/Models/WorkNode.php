<?php

namespace App\Models;

use App\Components\BaseResource;
use App\Components\WorkNodeResources\ControlResource;
use App\Components\WorkNodeResources\DataResource;
use App\Constants\WorkNodeTypeConstant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkNode extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'node';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'resources',
        'actions',
        'ct',
        'ut',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resources' => 'array',
            'actions' => 'array',
        ];
    }

    /**
     * Parse resources field and build Resource instances.
     *
     * @return BaseResource[]
     */
    public function getResources(): array
    {
        $resources = $this->resources ?? [];
        $resourceInstances = [];

        foreach ($resources as $resourceData) {
            if (!isset($resourceData['uri']) || !isset($resourceData['code'])) {
                continue;
            }

            if (isset($resourceData['type']) && $resourceData['type'] === WorkNodeTypeConstant::TYPE_CONTROL) {
                $resourceInstances[] = new ControlResource(
                    $resourceData['uri'],
                    $resourceData['code']
                );
                continue;
            }
            $resourceInstances[] = new DataResource(
                $resourceData['uri'],
                $resourceData['code'],
            );
        }

        return $resourceInstances;
    }
}
