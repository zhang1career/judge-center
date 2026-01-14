<?php

namespace App\Models;

use App\Components\WorknodeResource;
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
     * @return WorknodeResource[]
     */
    public function getResources(): array
    {
        $resources = $this->resources ?? [];
        $resourceInstances = [];

        foreach ($resources as $resourceData) {
            if (isset($resourceData['uri']) && isset($resourceData['code'])) {
                $resourceInstances[] = new WorknodeResource(
                    $resourceData['uri'],
                    $resourceData['code']
                );
            }
        }

        return $resourceInstances;
    }
}
