<?php

namespace App\Parsers;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait CsvParseTrait
{
    /** @var SymfonyStyle */
    public $io;
    /** @var string */
    public $projectDirectory;
    /** @var array */
    public $data = [];
    /** @var array */
    public $internal = [];
    /** @var \stdClass */
    public $ex;

    /**
     * Query CSV file from github
     */
    public function csv($content): ParseWrapper
    {
        if (isset($this->internal[$content])) {
            return $this->internal[$content];
        }
        
        $cache = $this->projectDirectory . getenv('CACHE_DIRECTORY');
        $filename = "{$cache}/{$content}.csv";

        // check cache and download if it does not exist
        if (!file_exists($filename)) {
            $this->io->text("Downloading: '{$content}.csv' for the first time ...");

            $githubFilename = str_ireplace('{content}', $content, getenv('GITHUB_CSV_FILE'));
            try {
                $githubFiledata = file_get_contents($githubFilename);
            } catch (\Exception $ex) {
                $this->io->error("Could not get the file: {$githubFilename} from GITHUB, are you sure it exists? Filenames are case-sensitive.");
                die;
            }

            if (!$githubFiledata) {
                $this->io->text('<error>Could not download file from github: '. $githubFilename);die;
            }
            
            $pi = pathinfo($filename);
            if (!is_dir($pi['dirname'])) {
                mkdir($pi['dirname'], 0777, true);
            }

            file_put_contents($filename, $githubFiledata);
            $this->io->text('✓ Download complete');
        }

        // grab wrapper
        $parser = new ParseWrapper($content, $filename);
        file_put_contents($filename.'.columns', json_encode($parser->columns, JSON_PRETTY_PRINT));
        file_put_contents($filename.'.offsets', json_encode($parser->offsets, JSON_PRETTY_PRINT));
        file_put_contents($filename.'.data', json_encode($parser->data, JSON_PRETTY_PRINT));
        
        $this->internal[$content] = $parser;

        return $parser;
    }

    /**
     * Set project directory
     */
    public function setProjectDirectory(string $projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;
        return $this;
    }

    /**
     * Get input folder
     */
    public function getInputFolder()
    {
        return $this->projectDirectory . getenv('INPUT_DIRECTORY');
    }

    /**
     * Get output folder
     */
    public function getOutputFolder()
    {
        return $this->projectDirectory . getenv('OUTPUT_DIRECTORY');
    }

    /**
     * Create an inout/output
     */
    public function setInputOutput(InputInterface$input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        return $this;
    }

    /**
     * Save to a file, if chunk size
     */
    public function save($filename, $chunkSize = 200, $dataset = false)
    {
        // create a chunk of data, if chunk size is 0/false we save the entire lot
        $dataset = $dataset ? $dataset : $this->data;
        $dataset = $chunkSize ? array_chunk($dataset, $chunkSize) : [ $dataset ];

        $folder = $this->projectDirectory . getenv('OUTPUT_DIRECTORY');

        // save each chunk
        $info = [];
        foreach ($dataset as $chunkCount => $data) {
            // build folder and filename
            $saveto = "{$folder}/chunk{$chunkCount}_{$filename}";

            // save chunked data
            file_put_contents($saveto, implode("\n", $data));
            $info[] = [
                $saveto,
                count($data),
                filesize($saveto)
            ];
        }

        return $info;
    }

    /**
     * Get either a full CSV or just the row from a single CSV file
     * Handy dandy function
     */
    private function getData($csvFilename, $index = null)
    {
        // if index is not null, fetch the row at that index
        if ($index !== null) {
            return $this->csvData[$csvFilename]->at($index);
        }

        return $this->csvData[$csvFilename];
    }
}
