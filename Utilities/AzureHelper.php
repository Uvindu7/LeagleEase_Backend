<?php
require '../vendor/autoload.php';

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

class AzureHelper
{
    private $blobClient;

    public function __construct($disableSSL = false)
    {
        $connectionString = "DefaultEndpointsProtocol=https;AccountName=legaleasenew;AccountKey=HT7gP1QzGCXvTpjs34OBZpf2Xs6Jdh9dx0cJRTA93I0NFogm3sZa9icGhAr6B3lIA/uetWjAo5ee+AStNiCEAQ==;EndpointSuffix=core.windows.net";

        // ⚡ Build HTTP client with optional SSL verify disabled
        $options = [];
        if ($disableSSL) {
            $options['http'] = [
                'verify' => false   // disables SSL cert validation
            ];
        }

        $this->blobClient = BlobRestProxy::createBlobService($connectionString, $options);
    }

    public function uploadFile($containerName, $fileTmpPath, $fileName)
    {
        try {
            if (!file_exists($fileTmpPath)) {
                return [
                    "success" => false,
                    "message" => "File not found at path: $fileTmpPath",
                ];
            }

            $content = fopen($fileTmpPath, "r");

            $options = new CreateBlockBlobOptions();
            $options->setContentType(mime_content_type($fileTmpPath));

            // ✅ Delete existing blob if it exists to allow overwrite
            try {
                $this->blobClient->deleteBlob($containerName, $fileName);
            } catch (ServiceException $e) {
                if ($e->getCode() !== 404) {
                    throw $e;
                }
            }

            // Upload the new blob
            $this->blobClient->createBlockBlob($containerName, $fileName, $content, $options);

            $blobUrl = "https://legaleasenew.blob.core.windows.net/$containerName/$fileName";
            return [
                "success" => true,
                "url" => $blobUrl,
            ];

        } catch (\Exception $e) {
            // ⚡ Detect SSL error and retry without verification
            if (strpos($e->getMessage(), 'cURL error 60') !== false) {
                error_log("⚠️ SSL verification failed, retrying without SSL check...");

                $helper = new self(true); // re-init with SSL disabled
                return $helper->uploadFile($containerName, $fileTmpPath, $fileName);
            }

            error_log("Azure upload error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => $e->getMessage(),
            ];
        }
    }
}
