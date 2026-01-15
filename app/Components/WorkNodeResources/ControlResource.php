<?php

namespace App\Components\WorkNodeResources;

use App\Components\BaseResource;
use App\Constants\WorkNodeTypeConstant;
use Exception;

class ControlResource extends BaseResource
{
    public function __construct(string $uri, string $code)
    {
        parent::__construct($uri, $code, WorkNodeTypeConstant::TYPE_CONTROL);

        $this->validate();
    }

    public static function fromArray(array $data): BaseResource
    {
        return new self(
            $data['uri'] ?? '',
            $data['code'] ?? '',
        );
    }

    /**
     * Handle the resource based on its type.
     * - If type is DATA, call getContent() to retrieve content from OSS
     * - If type is CONTROL, use uri as PHP method path and call the method
     *
     * @param mixed ...$args Arguments to pass to the method (for CONTROL type)
     * @return mixed The result of getContent() for DATA type, or the return value of the method for CONTROL type
     * @throws Exception
     */
    public function handle(...$args)
    {
        // Parse the URI as a PHP method path (e.g., "\\App\\Processors\\NoticeProcessor::sendEmail")
        $methodPath = $this->uri;

        // Check if it's a static method call (contains ::)
        if (str_contains($methodPath, '::')) {
            [$class, $method] = explode('::', $methodPath, 2);
            if (!class_exists($class)) {
                throw new Exception("Class not found: {$class}");
            }
            if (!method_exists($class, $method)) {
                throw new Exception("Method not found: {$class}::{$method}");
            }

            return call_user_func_array([$class, $method], $args);
        } else {
            // Assume it's a function call
            if (!function_exists($methodPath)) {
                throw new Exception("Function not found: {$methodPath}");
            }

            return call_user_func_array($methodPath, $args);
        }

    }
}
