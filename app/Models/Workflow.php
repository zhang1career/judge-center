<?php

namespace App\Models;

use App\Constants\WorkflowStatusConstant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'flow';

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
        'definition',
        'nodes',
        'status',
        'stage',
        'result',
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
            'definition' => 'array',
            'status' => 'integer',
            'stage' => 'integer',
            'result' => 'integer',
        ];
    }

    /**
     * Get the number of nodes in the workflow definition.
     *
     * @return int
     */
    public function length()
    {
        return sizeof($this->definition['nodes'] ?? []);
    }

    /**
     * Check if the workflow is in a processable state.
     *
     * @return bool
     */
    public function checkProcessable(): bool
    {
        return $this->status != WorkflowStatusConstant::DRAFT
            && $this->status != WorkflowStatusConstant::COMPLETED
            && $this->status != WorkflowStatusConstant::FAILED;
    }
}
