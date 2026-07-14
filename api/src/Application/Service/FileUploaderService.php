<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Exception\ValidationException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class FileUploaderService
{
    private const array MIME_MAP = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    /**
     * Uploads and validates a file.
     *
     * @param UploadedFileInterface $file
     * @param string $uploadPath Destination directory
     * @param string $prefix Prefix for the filename
     * @return string The generated filename (not the full path)
     * @throws ValidationException If validation fails
     */
    public function upload(UploadedFileInterface $file, string $uploadPath, string $prefix = ''): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException('Falha no upload do arquivo.');
        }

        // 1. Validate size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new ValidationException('O arquivo excede o limite de tamanho de 5MB.');
        }

        // 2. Validate MIME type (Server-side check)
        $file->getStream()->rewind();
        $buffer = $file->getStream()->getContents();
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($buffer);

        if (!\array_key_exists($mimeType, self::MIME_MAP)) {
            throw new ValidationException('Tipo de arquivo inválido. Apenas imagens (JPG, PNG, WEBP, GIF) são permitidas.');
        }

        // 3. Determine extension based on real MIME type
        $extension = self::MIME_MAP[$mimeType];

        // 4. Generate secure, unique filename
        $filename = \sprintf(
            '%s%s-%s.%s',
            $prefix !== '' && $prefix !== '0' ? $prefix . '-' : '',
            \time(),
            \bin2hex(\random_bytes(8)),
            $extension,
        );

        $destinationPath = $uploadPath . DIRECTORY_SEPARATOR . $filename;

        // Ensure directory exists
        if (!\is_dir($uploadPath) && (!\mkdir($uploadPath, 0o775, true) && !\is_dir($uploadPath))) {
            throw new RuntimeException(\sprintf('Diretório "%s" não pôde ser criado', $uploadPath));
        }

        // 5. Move file
        try {
            $file->moveTo($destinationPath);
        } catch (\Exception $exception) {
            throw new RuntimeException('Falha ao mover o arquivo enviado.', 0, $exception);
        }

        return $filename;
    }

    /**
     * Deletes a file from the upload path.
     *
     * @param string|null $filename Only the filename, not the full path
     * @param string $uploadPath Directory where the file is located
     */
    public function delete(?string $filename, string $uploadPath): void
    {
        if (!$filename) {
            return;
        }

        // Security: Use basename to prevent path traversal
        $basename = \basename($filename);
        $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $basename;

        if (\file_exists($fullPath) && \is_file($fullPath)) {
            \unlink($fullPath);
        }
    }
}
