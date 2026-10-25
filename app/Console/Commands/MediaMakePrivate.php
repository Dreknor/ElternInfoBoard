<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

/**
 * Bestehende Mediendateien von der öffentlichen Disk (storage/app/public → /storage/…) auf die
 * private Disk aus `media-library.disk_name` umziehen. Danach sind sie nur noch über /image/{id}
 * bzw. die App-API mit Rechteprüfung erreichbar.
 *
 * Idempotent: bereits umgezogene Einträge werden übersprungen, ein abgebrochener Lauf kann
 * einfach wiederholt werden (Dateien werden erst nach dem Umstellen des Eintrags gelöscht).
 */
class MediaMakePrivate extends Command
{
    protected $signature = 'media:make-private
        {--dry-run : Nur anzeigen, was umgezogen würde}
        {--keep-source : Dateien auf der alten Disk nicht löschen}';

    protected $description = 'Zieht Mediendateien von der öffentlichen Disk auf die private Medien-Disk um';

    public function handle(): int
    {
        $target = (string) config('media-library.disk_name');
        if ($target === '' || $target === 'public' || config("filesystems.disks.$target") === null) {
            $this->error("Ziel-Disk „{$target}“ ist nicht privat oder nicht konfiguriert (MEDIA_DISK prüfen).");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $to = Storage::disk($target);
        $moved = $missing = $failed = 0;

        Media::query()
            ->where(fn ($q) => $q->where('disk', '!=', $target)->orWhere('conversions_disk', '!=', $target))
            ->chunkById(200, function ($chunk) use ($target, $to, $dryRun, &$moved, &$missing, &$failed) {
                foreach ($chunk as $media) {
                    $generator = PathGeneratorFactory::create($media);
                    $from = Storage::disk($media->disk);

                    if (! $from->exists($generator->getPath($media).$media->file_name)) {
                        $this->warn("#{$media->id} {$media->file_name}: Datei fehlt auf Disk „{$media->disk}“ – übersprungen.");
                        $missing++;

                        continue;
                    }

                    if ($dryRun) {
                        $this->line("#{$media->id} {$media->file_name}: {$media->disk} → {$target}");
                        $moved++;

                        continue;
                    }

                    // Original, Conversions und responsive Bilder – je von der Disk, auf der sie liegen
                    $sources = [
                        [$media->disk, $generator->getPath($media)],
                        [$media->conversions_disk ?: $media->disk, $generator->getPathForConversions($media)],
                        [$media->conversions_disk ?: $media->disk, $generator->getPathForResponsiveImages($media)],
                    ];

                    try {
                        $copied = [];
                        foreach ($sources as [$disk, $dir]) {
                            if ($disk === $target) {
                                continue;
                            }
                            $source = Storage::disk($disk);
                            foreach ($source->allFiles($dir) as $file) {
                                if (isset($copied[$disk.':'.$file])) {
                                    continue;
                                }
                                $this->copy($source, $to, $file);
                                $copied[$disk.':'.$file] = [$disk, $file];
                            }
                        }

                        Media::query()->whereKey($media->id)->update(['disk' => $target, 'conversions_disk' => $target]);
                    } catch (\Throwable $e) {
                        $this->error("#{$media->id} {$media->file_name}: ".$e->getMessage());
                        $failed++;

                        continue;
                    }

                    if (! $this->option('keep-source')) {
                        foreach ($copied as [$disk, $file]) {
                            Storage::disk($disk)->delete($file);
                        }
                        foreach ($sources as [$disk, $dir]) {
                            $source = Storage::disk($disk);
                            if ($disk !== $target && $dir !== '' && $source->exists($dir) && $source->allFiles($dir) === []) {
                                $source->deleteDirectory($dir);
                            }
                        }
                    }

                    $moved++;
                }
            });

        $this->info(($dryRun ? 'Würde umziehen: ' : 'Umgezogen: ').$moved.', fehlend: '.$missing.', Fehler: '.$failed);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function copy(Filesystem $from, Filesystem $to, string $file): void
    {
        $stream = $from->readStream($file);
        if ($stream === null || ! $to->writeStream($file, $stream)) {
            throw new \RuntimeException("Kopieren von {$file} fehlgeschlagen.");
        }
        if (is_resource($stream)) {
            fclose($stream);
        }
        if ($to->size($file) !== $from->size($file)) {
            throw new \RuntimeException("Größe von {$file} stimmt nach dem Kopieren nicht.");
        }
    }
}
