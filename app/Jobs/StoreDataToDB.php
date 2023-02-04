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
        $zip        = new ZipArchive();
        $fileName   = Storage::disk('local')->path("Work_package-FS.zip");
        $fileOpened = $zip->open($fileName);
        if ($fileOpened) {
            $zip->extractTo(Storage::disk('local')->path('public'));
            $zip->close();
        }

        $filePath = Storage::disk('local')->path("public/Work_package/FilteredDataHuman");

        $fopen = fopen($filePath, 'r');
        $fread = fread($fopen, 1000000);
        do {
            $matches = $this->useRegex($fread);
            foreach ($matches as $match) {
                $count = count($match);
                for ($i = 0; $i < $count - 1; $i++) {
                    $array     = substr($fread, $match[$i][1], $match[$i + 1][1] - ($match[$i][1]));
                    $string    = Str::endsWith($array, ',') ? rtrim($array, ',') : $array;
                    $json      = json_decode($string, true);
                    $timestamp = $json['timestamp']['$date']['$numberLong'];
                    $instances = Arr::get($json, 'instances');
                    foreach ($instances as $person => $instance) {
                        $posX = Arr::get($instance, 'pos_x');
                        $posY = Arr::get($instance, 'pos_y');
                        PersonPositions::updateOrCreate([
                            'person'    => $person,
                            'timestamp' => $timestamp
                        ], [
                            'pos_x'    => $posX,
                            'pos_y'    => $posY,
                            'raw_data' => json_encode($instance)
                        ]);
                    }
                }
                $nextOffset = $match[$count - 1][1];
            }
            $fread = fread($fopen, 1000000);
            dd($nextOffset);
        } while (true);

        dd('hdhdhd');
    }


    function useRegex($input): array
    {
        $regex = '/{\n  "timestamp": {\n    "\$date": {[^}]*}\n  },\n  "_id": {[^}]*},\n  "instances": {/im';
        preg_match_all($regex, $input, $matches, PREG_OFFSET_CAPTURE);
        return $matches;
    }
}
