<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GenerateImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-image';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create image from json';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = Http::get('https://ab-file-explorer.athleticnext.workers.dev/?file=regular');

        if (!$response->successful()) {
            $msg = 'Error occured with status code: ' . $response->status();
            $this->error($msg);

            return;
        }
        
        $data = $this->buildTree(json_decode($response->body(), true));
        $lines = [];

        $this->parseLines($data['filepaths'], $lines);

        $fontSize = 30;
        $lineHeight = 20;
        $width = 0;

        foreach ($lines as $line) {
            $lineWidth = imagefontwidth($fontSize) * strlen($line);
            if ($lineWidth > $width) {
                $width = $lineWidth;
            }
        }

        $image = imagecreatetruecolor($width + 40, count($lines) * $lineHeight + 20);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefilledrectangle($image, 0, 0, $width + 20, count($lines) * $lineHeight + 20, $black);

        $y = 10;
        foreach ($lines as $line) {
            imagestring($image, $fontSize, 20, $y, $line, $white);
            $y += $lineHeight;
        }

        $path = storage_path('app/public/filetree.jpg');
        imagejpeg($image, $path, 90);
        imagedestroy($image);

        $this->info("Image saved to: {$path}");
    }

    public function parseLines(array $tree, array &$lines, int $depth = 0): void
    {
        foreach ($tree as $name => $subtree) {
            $lines[] = str_repeat('  ', $depth) . $name;

            if (is_array($subtree)) {
                $this->parseLines($subtree, $lines, $depth + 1);
            }
        }
    }

    function buildTree(array $paths): array
    {
        $result = [];

        foreach ($paths['filepaths'] as $path) {
            $parts = explode('/', $path);
            $current = &$result['filepaths'];
    
            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }
    
        return $result;
    }

}