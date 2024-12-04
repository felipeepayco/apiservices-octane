<?php

namespace App\Helpers;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class ClientS3
{
    private $s3Client;

    public function __construct()
    {
        $credentials = [
            'version' => getenv("AWS_VERSION"),
            'region' => getenv("AWS_REGION"),
        ];

        if (getenv("AWS_KEY")) {
            $credentials["credentials"] = [
                'key' => getenv("AWS_KEY"),
                'secret' => getenv("AWS_SECRET")
            ];
        }

        $this->s3Client = new S3Client($credentials);
    }

    public function uploadFileAws($bucketName, $localfile, $path, $type = null, $private = false, $returnObjectURL = false)
    {
        try {
            $object = [
                'Bucket' => $bucketName,
                'Key' => $path,
                'Body' => fopen($localfile, 'r'),
            ];

            if ($type) {
                $object['ContentType'] = $type === 'pdf' ? 'application/pdf' : $type;
            }

            if (!$private) {
                $object['ACL'] = 'public-read';
            }

            $responseS3 = $this->s3Client->putObject($object);
            $titleResponse = true;
            $textResponse = 'File was saved Successfully';
            $data = $returnObjectURL ? $responseS3['ObjectURL'] : $path;
        } catch (S3Exception $s3Exception) {
            $titleResponse = false;
            $textResponse = 'There was a problem saving';
            $data = ['error' => $s3Exception->getMessage()];
        }

        return [
            'titleResponse' => $titleResponse,
            'textResponse' => $textResponse,
            'lastAction' => 'Save file',
            'data' => $data,
        ];
    }
}
