<?php

namespace App\Domains\Administration\Private\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogsController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        $selectedFile = $request->get('file');
        $availableFiles = $this->loadAvailableFiles();
        
        // Default to most recent file if none selected
        if (!$selectedFile && !empty($availableFiles)) {
            $selectedFile = $availableFiles[0]['file'];
        }
        
        $lines = [];
        if ($selectedFile) {
            $lines = $this->getLogLines($selectedFile);
        }
        
        return view('administration::pages.logs', [
            'availableFiles' => $availableFiles,
            'selectedFile' => $selectedFile,
            'lines' => $lines,
        ]);
    }
    
    public function download(string $file): StreamedResponse
    {
        $path = $this->resolveFilePath($file);
        
        abort_unless($path && is_file($path), 404);
        
        return response()->streamDownload(function () use ($path) {
            $stream = fopen($path, 'rb');
            while (!feof($stream)) {
                echo fread($stream, 1024 * 64);
                @ob_flush();
                flush();
            }
            fclose($stream);
        }, basename($path), [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
    
    /**
     * Load available log files from storage/logs directory.
     * 
     * @return array<int, array{file:string, path:string, mtime:int, size:int}>
     */
    private function loadAvailableFiles(): array
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
        
        // Sort by modification time descending
        usort($files, fn ($a, $b) => $b['mtime'] <=> $a['mtime']);
        
        return $files;
    }
    
    /**
     * Resolve file path and prevent directory traversal attacks.
     */
    private function resolveFilePath(string $file): ?string
    {
        if (!$file) {
            return null;
        }
        
        // Prevent directory traversal
        $clean = basename($file);
        $path = storage_path('logs' . DIRECTORY_SEPARATOR . $clean);
        
        if (!str_starts_with($path, storage_path('logs'))) {
            return null;
        }
        
        return $path;
    }
    
    /**
     * Get the last N lines from a log file, preserving natural order.
     * 
     * @return array<int, string>
     */
    private function getLogLines(string $file, int $lines = 1000): array
    {
        $path = $this->resolveFilePath($file);
        
        if (!$path || !is_file($path) || !is_readable($path)) {
            return [];
        }
        
        return $this->tailOrdered($path, $lines);
    }
    
    /**
     * Efficiently read the last N lines of a potentially large file, preserving natural order.
     * 
     * @return array<int, string>
     */
    private function tailOrdered(string $path, int $lines = 1000): array
    {
        $buffer = '';
        $chunkSize = 1024 * 4; // 4KB chunks
        $fp = fopen($path, 'rb');
        if (!$fp) {
            return [];
        }
        
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
        if ($arr === false) {
            return [];
        }
        
        $arr = array_slice($arr, -$lines);
        return $arr; // keep natural order so newest lines are at the bottom
    }
}
