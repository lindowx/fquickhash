<?php

namespace Lindowx\FQuickHash;

use Psr\Log\LoggerInterface;

class Hash
{
    const MIN_FILE_SIZE = 8388608;
    const SAMPLE_SIZE = 4096;
    const MAX_SAMPLE_TIMES = 1024;

    /**
     * @var string Base algorithm
     */
    protected $algo;

    /**
     * @param LoggerInterface $logger Logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        Log::setLogger($logger);
        return $this;
    }

    /**
     * Hash constructor.
     * @param string            $baseHashAlgo   Accept algorithm listed in hash_algos()
     * @throws HashException
     */
    public function __construct($baseHashAlgo = 'md5')
    {
        $this->algo = $baseHashAlgo;
        Log::debug('Base hash algorithm=' . $baseHashAlgo);
        $acceptedAlgos = hash_algos();
        if (! in_array($baseHashAlgo, $acceptedAlgos)) {
            throw new HashException('Invalid hash algorithm: ' . $baseHashAlgo);
        }
    }

    protected function hash($data, $raw = false)
    {
        return hash($this->algo, $data, $raw);
    }

    protected function hashFile($filename, $raw = false)
    {
        return hash_file($this->algo, $filename, $raw);
    }

    /**
     * @param string    $filename   File name
     * @param bool      $raw        Output as raw data(false as default)
     * @return string
     * @throws HashException
     */
    public function getHash($filename, $raw = false)
    {
        if (! file_exists($filename)) {
            throw new HashException('File not found: ' . $filename);
        }

        Log::debug("Raw output=" . json_encode($raw));

        $filesize = filesize($filename);
        Log::debug("File size={$filesize}");
        if ($filesize < self::MIN_FILE_SIZE) {
            return $this->hashFile($filename, $raw);
        }

        $seed = null;
        $sampleFp = fopen($filename, 'r');

        if (! $sampleFp) {
            throw new HashException('Failed to open data file for reading');
        }

        $paddingMin = self::SAMPLE_SIZE * 1024;
        $sampleTimes = round($filesize / (self::SAMPLE_SIZE + $paddingMin));

        Log::debug("Calc count samples={$sampleTimes}");
        if ($sampleTimes > self::MAX_SAMPLE_TIMES) {
            $sampleTimes = self::MAX_SAMPLE_TIMES;
        }

        Log::debug("Real count samples= {$sampleTimes}");

        $step = round($filesize / $sampleTimes);
        $seed = '' . $filesize;
        for ($i = 0; $i < $filesize; $i += $step) {
            fseek($sampleFp, $i);
            $sample = fread($sampleFp, self::SAMPLE_SIZE);
            $seed .= '-' . $this->hash($sample);
        }
        fclose($sampleFp);

        return empty($seed) ? false : $this->hash($seed, $raw);
    }
}
