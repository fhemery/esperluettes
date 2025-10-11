<?php

namespace App\Domains\Admin\Filament\Pages;

use Filament\Actions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ViewLogs extends Page
{
    protected static ?string $slug = 'view-logs';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string $view = 'admin::pages.view-logs';

    protected static ?string $navigationGroup = null;

    public ?string $file = null; // relative filename within storage/logs

    /** @var array<int, string> */
    public array $lines = [];

    /** @var array<int, array{file:string, path:string, mtime:int, size:int}> */
    public array $availableFiles = [];

    public static function getNavigationLabel(): string
    {
        return __('admin::pages.view_logs.nav_label');
    }

    public static function getNavigationSort(): ?int
    {
        // Just below System Maintenance (-2)
        return -1;
    }

    public function getTitle(): string
    {
        return __('admin::pages.view_logs.nav_label');
    }

    public function getHeading(): string
    {
        return __('admin::pages.view_logs.nav_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin::pages.groups.tech');
    }

    public static function shouldRegisterNavigation(): bool
    {
        /** @var \App\Domains\Auth\Private\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasRole('tech-admin') ?? false;
    }

    public static function canAccess(): bool
    {
        /** @var \App\Domains\Auth\Private\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasRole('tech-admin') ?? false;
    }

    public function mount(?string $file = null): void
    {
        $this->loadAvailableFiles();

        // Default file: most recent
        $this->file = $file ?: ($this->availableFiles[0]['file'] ?? null);

        $this->refreshLines();
    }

    public function updatedFile(): void
    {
        $this->refreshLines();
    }


    public function refresh(): void
    {
        $this->loadAvailableFiles();
        $this->refreshLines();
    }

    public function download()
    {
        $path = $this->resolveSelectedPath();
        abort_unless($path && is_file($path), 404);

        $downloadName = basename($path);
        return response()->streamDownload(function () use ($path) {
            $stream = fopen($path, 'rb');
            while (!feof($stream)) {
                echo fread($stream, 1024 * 64);
                @ob_flush();
                flush();
            }
            fclose($stream);
        }, $downloadName, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    protected function loadAvailableFiles(): void
    {
        $dir = storage_path('logs');
        $files = [];
        if (is_dir($dir)) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.log') as $path) {
                $files[] = [
                    'file' => basename($path),
                    'path' => $path,
                    'mtime' => (int) @filemtime($path),
                    'size' => (int) @filesize($path),
                ];
            }
        }

        // Sort by mtime desc
        usort($files, fn ($a, $b) => $b['mtime'] <=> $a['mtime']);
        $this->availableFiles = $files;
    }

    protected function resolveSelectedPath(): ?string
    {
        if (!$this->file) {
            return null;
        }
        // Prevent directory traversal
        $clean = basename($this->file);
        $path = storage_path('logs' . DIRECTORY_SEPARATOR . $clean);
        if (!str_starts_with($path, storage_path('logs'))) {
            return null;
        }
        return $path;
    }

    protected function refreshLines(): void
    {
        $path = $this->resolveSelectedPath();
        $this->lines = [];
        if (!$path || !is_file($path) || !is_readable($path)) {
            return;
        }

        $this->lines = $this->tailOrdered($path, 1000);
    }

    

    /**
     * Efficiently read the last N lines of a potentially large file, preserving natural order.
     *
     * @return array<int,string>
     */
    protected function tailOrdered(string $path, int $lines = 1000): array
    {
        $buffer = '';
        $chunkSize = 1024 * 4; // 4KB chunks
        $fp = fopen($path, 'rb');
        if (!$fp) return [];
        $pos = -1;
        $lineCount = 0;
        $fileSize = filesize($path) ?: 0;

        $data = '';
        // Start reading from the end in chunks
        while (-$pos < $fileSize) {
            $seek = max($pos - $chunkSize, -$fileSize);
            $readLen = abs($seek - $pos);
            fseek($fp, $seek, SEEK_END);
            $chunk = fread($fp, $readLen);
            $data = $chunk . $data;
            $pos = $seek;

            // Count lines
            $lineCount = substr_count($data, "\n");
            if ($lineCount >= $lines + 1) { // +1 to ensure we have enough delimiters
                break;
            }
        }
        fclose($fp);

        $arr = preg_split("/\r?\n/", rtrim($data, "\r\n"));
        if ($arr === false) return [];
        $arr = array_slice($arr, -$lines);
        return $arr; // keep natural order so newest lines are at the bottom
    }
}
