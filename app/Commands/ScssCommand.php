<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use LaravelZero\Framework\Commands\Command;

class ScssCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'scss:convert {input : The path of the input scss file}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Convert scss file into a css file';

    /**
     * The input file path
     *
     * @var string
     */
    protected $filePath;

    /**
     * The input file content
     *
     * @var string
     */
    protected $content;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $isFileLoaded = $this->task("Read the input file", function () {
            return $this->readInputFile();
        });

        if (!$isFileLoaded) {
            return;
        }

        $this->task("Check the input file structure", function () {
            return $this->checkInputFile();
        });
        $this->task("Convert scss to css", function () {
            return $this->convertInputFile();
        });
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    /**
     * Read the file content of $filePath
     *
     * @return bool
     */
    private function readInputFile(): bool
    {
        try {
            $this->filePath = $this->argument('input');
            $this->content = File::get($this->filePath);
            return true;
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $exception) {
            return false;
        }
    }

    /**
     * Check the file structure of $this->content : must be scss
     *
     * @return bool
     */
    private function checkInputFile(): bool
    {
        return true;
    }

    /**
     * Convert $this->content from scss to css
     *
     * @return string
     */
    private function convertInputFile(): string
    {
        // Split the content in an array on new line "\n"
        $contentLines = explode("\n", $this->content);

        $class = [];
        $classNames = [];
        foreach ($contentLines as $line) {
            // We exclude blank line
            if ($line == '') {
                continue;
            }

            // We got a new class
            if (strpos($line, '{')) {
                // The class name is the line content without bracket
                $classNames[] = str_replace(' {', '', $line);
                continue;
            }

            // If end of class definition => remove last class name
            if (strpos($line, '}') !== false) {
                array_pop($classNames);
                continue;
            }

            $class[implode(' ', $classNames)][] = $line;
        }

        // We generate the new content with the array of class
        $newContent = '';
        foreach ($class as $key => $classItem) {
            $newContent .= "\n" . $key . " { \n" . implode("\n", $classItem) . "\n}";
        }

        $pathInfo = pathinfo($this->filePath);
        $newFilePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-compiled.css';
        File::put($newFilePath, $newContent);

        Log::info('New file generated : ' . $newFilePath);
        return $newFilePath;
    }
}
