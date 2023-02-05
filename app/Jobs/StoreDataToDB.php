<?php

namespace App\Jobs;

use App\Models\PersonPositions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use ZipArchive;

class StoreDataToDB implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $output     = new ConsoleOutput();
        $zip        = new ZipArchive();
        $fileName   = Storage::disk('local')->path("Work_package-FS.zip");
        $fileOpened = $zip->open($fileName);
        if ($fileOpened) {
            $zip->extractTo(Storage::disk('local')->path('public'));
            $zip->close();
        }

        $filePath = Storage::disk('local')->path("public/Work_package/FilteredDataHuman");

        $fopen = fopen($filePath, 'r');
        $size  = fstat($fopen)['size'];
        // chunk data by 1mb
        $chunkSize       = 10000;
        $chunks          = $size / $chunkSize;
        $progress        = new ProgressBar($output, $chunks);
        $endOfFile       = false;
        $currentPosition = $size;
        $offSet          = 0;
        while (!$endOfFile && $currentPosition >= 0) {
            $length  = min($currentPosition, $chunkSize);
            $fread   = fread($fopen, $length);
            $matches = $this->useRegex($fread);
            if (count($matches) == 0) {
                break;
            }
            foreach ($matches as $match) {
                $count = count($match);

                if ($count > 1) {
                    for ($i = 0; $i < $count - 1; $i++) {
                        $array     = substr($fread, $match[$i][1], $match[$i + 1][1] - ($match[$i][1]));
                        $endOfFile = Str::endsWith($array, ']');
                        if ($endOfFile) {
                            $array = rtrim($array, ']');
                        }
                        $string = Str::endsWith($array, ',') ? rtrim($array, ',') : $array;
                        $this->storePosition($string);
                    }

                    $nextOffset = $match[$count - 1][1];
                    $progress->advance();
                    //sleep(1);

                } elseif ($count == 1) {

                    $array     = substr($fread, $match[0][1]);
                    $endOfFile = Str::endsWith($array, ']');
                    if ($endOfFile) {
                        $array = rtrim($array, ']');
                    }
                    $this->storePosition($array);
                } else {
                    break;
                }

            }

            $offSet += $nextOffset;
            fseek($fopen, $offSet);

            $currentPosition -= $nextOffset;

        }
        $progress->finish();
        fclose($filePath);
        Storage::disk('local')->deleteDirectory('public/Work_package');
        dd('end');
    }


    function useRegex($input): array
    {
        $regex = '/{\n  "timestamp": {\n    "\$date": {[^}]*}\n  },\n/im';
        preg_match_all($regex, $input, $matches, PREG_OFFSET_CAPTURE);
        return $matches;
    }

    function storePosition($string)
    {
        $json      = json_decode($string, true);
        $timestamp = $json['timestamp']['$date']['$numberLong'];
        $oid       = $json['_id']['$oid'];

        $instances = Arr::get($json, 'instances');
        foreach ($instances as $person => $instance) {
            $posX = Arr::get($instance, 'pos_x');
            $posY = Arr::get($instance, 'pos_y');
            PersonPositions::updateOrCreate([
                'person'    => $person,
                'oid'       => $oid,
                'timestamp' => $timestamp,
            ], [
                'pos_x'    => $posX,
                'pos_y'    => $posY,
                'raw_data' => json_encode($instance)
            ]);
        }
    }
}
