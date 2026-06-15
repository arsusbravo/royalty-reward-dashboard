<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class FaceRecognitionService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.face_api.base_url'), '/');
        $this->timeout = config('services.face_api.timeout', 30);
    }

    /**
     * Register a face with the external API.
     * POST /faces/register  multipart: file, name
     * Returns: { id, name, message }
     */
    public function registerFace(UploadedFile|string $image, string $name): array
    {
        return $this->postMultipart("{$this->baseUrl}/faces/register", [
            'name' => $name,
            'file' => $this->buildCurlFile($image),
        ]);
    }

    /**
     * Identify a face against all registered faces.
     * POST /faces/identify  multipart: file
     * Returns: { matched, name, id, similarity }
     */
    public function identifyFace(UploadedFile|string $image): array
    {
        return $this->postMultipart("{$this->baseUrl}/faces/identify", [
            'file' => $this->buildCurlFile($image),
        ]);
    }

    /**
     * Compare two faces.
     * POST /faces/compare  multipart: file1, file2
     * Returns: { similarity, is_same_person }
     */
    public function compareFaces(UploadedFile|string $image1, UploadedFile|string $image2): array
    {
        return $this->postMultipart("{$this->baseUrl}/faces/compare", [
            'file1' => $this->buildCurlFile($image1),
            'file2' => $this->buildCurlFile($image2),
        ]);
    }

    /**
     * Delete a registered face by its API ID.
     * DELETE /faces/{face_id}
     */
    public function deleteFace(string $faceId): void
    {
        $ch = curl_init("{$this->baseUrl}/faces/{$faceId}");
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);

        $body  = curl_exec($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("Face API connection error: {$error}");
        }

        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException("Face API DELETE returned HTTP {$code}: {$body}");
        }
    }

    /**
     * Check connectivity to the face API.
     */
    public function isAvailable(): bool
    {
        $ch = curl_init("{$this->baseUrl}/faces");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_NOBODY         => true,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 400;
    }

    // -------------------------------------------------------------------------

    private function postMultipart(string $url, array $fields): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);

        $body  = curl_exec($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("Face API connection error: {$error}");
        }

        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException("Face API returned HTTP {$code}: {$body}");
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Face API returned non-JSON response.');
        }

        return $decoded;
    }

    private function buildCurlFile(UploadedFile|string $image): \CURLFile
    {
        if ($image instanceof UploadedFile) {
            return new \CURLFile(
                $image->getRealPath(),
                $image->getMimeType() ?? 'image/jpeg',
                $image->getClientOriginalName()
            );
        }

        return new \CURLFile($image, mime_content_type($image) ?: 'image/jpeg', basename($image));
    }
}
