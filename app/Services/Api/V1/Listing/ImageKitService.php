<?php

namespace App\Services\Api\V1\Listing;

use ImageKit\ImageKit;
use Illuminate\Http\UploadedFile;

class ImageKitService
{
    protected ImageKit $ik;

    public function __construct()
    {
        $this->ik = new ImageKit(
            config('services.imagekit.public_key'),
            config('services.imagekit.private_key'),
            config('services.imagekit.url_endpoint')
        );
    }

    /**
     * Upload a file to ImageKit and return the success payload (incl. url).
     * @return array{url:string,fileId:string,name?:string,width?:int,height?:int,size?:int,thumbnailUrl?:string}
     */
    public function upload(UploadedFile $file, string $folder = '/products', ?string $fileName = null): array
    {
        $fileName ??= time().'_'.$file->getClientOriginalName();

        // Base64 is friendlier on Windows temp files
        $binary = file_get_contents($file->getRealPath());
        if ($binary === false) {
            throw new \RuntimeException('Unable to read uploaded file.');
        }
        $base64 = 'data:'.$file->getMimeType().';base64,'.base64_encode($binary);

        try {
            $res = $this->ik->uploadFile([
                'file'              => $base64,
                'fileName'          => $fileName,
                'folder'            => rtrim($folder, '/'),
                'useUniqueFileName' => true,
                'isPrivateFile'     => false,
                'transformation'    => [
                    'post' => [
                        [
                            'type' => 'transformation',
                            'value' => 'h-2000,w-2000'
                        ]
                    ]
                ]
            ]);
            \Log::info('Upload successful: ' . json_encode($res));
        } catch (\Exception $e) {
            \Log::error('ImageKit upload failed: ' . json_encode($e->getResponse(), JSON_PRETTY_PRINT));
        }


        // --- normalize response across SDK versions ---
        $error   = property_exists($res, 'err')     ? $res->err
            : (property_exists($res, 'error')  ? $res->error : null);

        $payload = property_exists($res, 'success') ? $res->success
            : (property_exists($res, 'result') ? $res->result : null);

        if ($error) {
            $msg = is_object($error) && property_exists($error, 'message') ? $error->message : 'ImageKit upload failed';
            \Log::error('ImageKit upload error', ['error' => $error]);
            throw new \RuntimeException($msg);
        }

        if (!$payload) {
            \Log::error('ImageKit upload returned empty payload', ['response' => $res]);
            throw new \RuntimeException('ImageKit returned an empty response.');
        }

        // cast to array safely
        return is_array($payload) ? $payload : (array) $payload;
    }

}
