<?php

namespace Flagrow\Sitemap\Disk;

use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;

class Sitemap
{
    /**
     * @var string
     */
    protected $filename;
    /**
     * @var Builder
     */
    protected $query;
    /**
     * @var callable
     */
    protected $callback;
    /**
     * @var string
     */
    protected $tmpDir;

    public function __construct(string $filename, Builder $query, callable $callback, string $tmpDir = null)
    {
        $this->filename = $filename;
        $this->query = $query;
        $this->callback = $callback;
        $this->tmpDir = $tmpDir;
    }

    /**
     * Limit the number of entries to 50.000.
     *
     * @return array|string[]
     */
    public function write(): array
    {
        $directory = $this->tmpDir ?? public_path('sitemaps');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $this->chunk($directory);
    }

    public function each($item)
    {
        if ($callback = $this->callback) {
            $item = $callback($item);
        }

        return $item;
    }

    protected function gzCompressFile($source, $level = 9)
    {
        $dest = $source . '.gz';
        $mode = 'wb' . $level;
        $error = false;
        if ($fp_out = gzopen($dest, $mode)) {
            if ($fp_in = fopen($source,'rb')) {
                while (!feof($fp_in))
                    gzwrite($fp_out, fread($fp_in, 1024 * 512));
                fclose($fp_in);
            } else {
                $error = true;
            }
            gzclose($fp_out);
        } else {
            $error = true;
        }
        if ($error)
            return false;
        else
            return $dest;
    }

    protected function view(): Factory
    {
        return app(Factory::class);
    }

    /**
     * @param string $directory
     * @return array
     */
    protected function chunk(string $directory): array
    {
        $index = 0;
        $filesWritten = [];

        $this->query->chunk(5000, function ($query) use (&$index, &$filesWritten, $directory) {
            $filename = "sitemap-{$this->filename}-{$index}.xml";

            $stream = fopen($path = "$directory/$filename", 'w+');

            fwrite($stream, <<<EOM
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
EOM
            );

            $query->each(function ($item) use (&$stream) {
                $url = $this->each($item);

                fwrite(
                    $stream,
                    $this->view()->make('flagrow-sitemap::url')->with('url', $url)->render()
                );
            });

            fwrite($stream, <<<EOM
</urlset>
EOM
            );

            $index++;

            fclose($stream);

            if ($gzipped = $this->gzCompressFile($path)) {
                unlink($path);
            }


            $filesWritten[] = str_replace($directory, null, $gzipped ?? $path);
        });

        return $filesWritten;
    }
}
