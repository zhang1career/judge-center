<?php

namespace App\Components\WorkNodeResources;

use App\Components\BaseResource;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Exception;

class DataResource extends BaseResource
{
    public function __construct(string $uri, string $code)
    {
        parent::__construct($uri, $code, self::TYPE_DATA);

        $this->validate();
    }

    public static function fromArray(array $data): DataResource
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
