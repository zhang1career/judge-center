<?php

namespace App\Components;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Exception;

class WorknodeResource
{
    /**
     * @var string $uri The URI of the resource in OSS
     */
    private string $uri;

    /**
     * @var string $code Resource meta code
     */
    private string $code;

    public function __construct(string $uri, string $code)
    {
        $this->uri = $uri;
        $this->code = $code;

        $this->validate();
    }

    public static function fromArray(array $data): WorknodeResource
    {
        return new WorknodeResource(
            $data['uri'] ?? '',
            $data['code'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'code' => $this->code,
        ];
    }

    private function validate()
    {
        if (empty($this->uri)) {
            throw new \InvalidArgumentException('Resource URI cannot be empty');
        }
        if (empty($this->code)) {
            throw new \InvalidArgumentException('Resource code cannot be empty');
        }
    }

    public static function getValidationRules(): array
    {
        return [
            'resources' => 'array',
            'resources.*.uri' => 'required_with:resources|string',
            'resources.*.code' => 'required_with:resources|string',
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

    /**
     * Get content from OSS using boto3-compatible S3 API
     *
     * @return string|null The content of the object, or null if not found
     * @throws Exception
     */
    public function getContent(): ?string
    {
        $endpoint = config('services.oss.endpoint');
        $accessKeyId = config('services.oss.access_key');
        $secretAccessKey = config('services.oss.secret_key');
        $region = config('services.oss.region');
        $bucket = config('services.oss.bucket');
        if (!$endpoint || !$bucket) {
            throw new Exception('OSS configuration is incomplete. Please set OSS_ENDPOINT and OSS_BUCKET in your .env file.');
        }

        // Parse URI to extract bucket and key
        // URI format could be: s3://bucket/key or just the key
        $key = $this->parseUri($this->uri);

        try {
            // Create S3 client with OSS endpoint (OSS is S3-compatible)
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => $region,
                'endpoint' => $endpoint,
                'credentials' => [
                    'key' => $accessKeyId,
                    'secret' => $secretAccessKey,
                ],
                'use_path_style_endpoint' => true, // Required for OSS
            ]);

            // Get object from OSS
            $result = $s3Client->getObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);

            // Return the body content
            return $result['Body']->getContents();
        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() === 'NoSuchKey' || $e->getStatusCode() === 404) {
                return null;
            }
            throw new Exception('Failed to get content from OSS: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Parse URI to extract the object key
     *
     * @param string $uri
     * @return string
     */
    private function parseUri(string $uri): string
    {
        // If URI starts with s3://, parse it
        if (str_starts_with($uri, 's3://')) {
            $uri = substr($uri, 5); // Remove 's3://'
            $parts = explode('/', $uri, 2);
            if (count($parts) === 2) {
                return $parts[1]; // Return the key part
            }
            return $parts[0];
        }

        // If URI starts with http:// or https://, extract the key from the path
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            $parsedUrl = parse_url($uri);
            $path = $parsedUrl['path'] ?? '';
            // Remove leading slash
            return ltrim($path, '/');
        }

        // Otherwise, treat the URI as the key directly
        return $uri;
    }
}
