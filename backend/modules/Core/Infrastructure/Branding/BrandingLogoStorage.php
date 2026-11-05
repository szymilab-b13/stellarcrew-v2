<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Branding;

use function in_array;
use function mkdir;
use function sprintf;
use function strtolower;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class BrandingLogoStorage
{
    public const array ALLOWED_EXTENSIONS = ['png', 'svg', 'jpg', 'jpeg', 'webp'];

    public const string RELATIVE_DIRECTORY = 'var/uploads/branding';

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {
    }

    public function store(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = strtolower($file->guessExtension() ?? 'png');
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = 'png';
        }

        $targetDirectory = $this->projectDir.'/'.self::RELATIVE_DIRECTORY;
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0o755, true);
        }

        $filename = sprintf('logo.%s', $extension);
        $file->move($targetDirectory, $filename);

        return self::RELATIVE_DIRECTORY.'/'.$filename;
    }
}
