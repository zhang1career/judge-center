<?php

namespace App\Components;

use App\Constants\WorkNodeTypeConstant;
use InvalidArgumentException;

abstract class BaseResource
{
    /**
     * @var string $uri The URI of the resource in OSS
     */
    protected string $uri;

    /**
     * @var string $code Resource meta code
     */
    protected string $code;

    /**
     * @var string $type Resource type: DATA or CONTROL
     */
    protected string $type;


    public function __construct(string $uri, string $code, string $type)
    {
        $this->uri = $uri;
        $this->code = $code;
        $this->type = $type;

        $this->validate();
    }

    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'code' => $this->code,
            'type' => $this->type,
        ];
    }

    protected function validate()
    {
        if (empty($this->uri)) {
            throw new InvalidArgumentException('Resource URI cannot be empty');
        }
        if (empty($this->code)) {
            throw new InvalidArgumentException('Resource code cannot be empty');
        }
        if (!in_array($this->type, [WorkNodeTypeConstant::TYPE_DATA, WorkNodeTypeConstant::TYPE_CONTROL])) {
            throw new InvalidArgumentException('Resource type must be either DATA or CONTROL, invalid type: ' . $this->type);
        }
    }

    public static function getValidationRules(): array
    {
        return [
            'resources' => 'array',
            'resources.*.uri' => 'required_with:resources|string',
            'resources.*.code' => 'required_with:resources|string',
            'resources.*.type' => 'sometimes|int|in:0,1',
        ];
    }


    /**
     * Getters
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): string
    {
        return $this->type;
    }


    /**
     * Handle the resource based on its type.
     */
    public function handle(...$args)
    {
    }
}
