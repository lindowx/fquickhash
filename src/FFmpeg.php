<?php

namespace Lindowx\FQuickHash;

class FFmpeg
{
    /**
     * @var string
     */
    protected static $binDir;

    protected static function detectBinPath($binary)
    {
        if (! self::$binDir) {
            $os = strtolower(php_uname('s'));
            if (strpos($os, 'linux') !== false) {
                self::$binDir = __DIR__ . '/../ffmpeg/linux';
            } else if (strpos($os, 'windows') !== false) {
                self::$binDir = __DIR__ . '/../ffmpeg/windows';
            } else {
                throw new HashException('Operating system is not supported');
            }

            switch (PHP_INT_SIZE) {
                case 8:
                    self::$binDir .= '/x64';
                    break;
                default:
                    self::$binDir .= '/x86';
            }
        }

        return self::$binDir . '/' . $binary;
    }

    public static function ffprobe($filename)
    {
        $filename = escapeshellarg($filename);
        $ffprobeBin = self::detectBinPath('ffprobe');
        $cmd = sprintf('%s -v quiet -show_streams -show_format -show_chapters -show_programs -print_format json %s', $ffprobeBin, $filename);

        $pp = popen($cmd, 'r');
        if (! $pp) {
            throw new HashException('Failed to execute ffprobe command: ' . $cmd);
        }

        $output = '';
        while (!feof($pp)) {
            $output .= fread($pp, 1024);
        }
        pclose($pp);

        $output = json_decode($output, true);
        if (empty($output)) {
            echo $cmd;
            throw new HashException('Failed to apply json_decode on command output: ' . $output);
        }

        unset($output['format']['filename']);
        return $output;
    }
}
