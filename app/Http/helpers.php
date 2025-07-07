<?php

use Illuminate\Support\Facades\Log;

if (! function_exists('formatBytes')) {
    function formatBytes($size, $decimals = 0) {
        $unit = array(
            '0' => 'Byte',
            '1' => 'KiB',
            '2' => 'MiB',
            '3' => 'GiB',
            '4' => 'TiB',
            '5' => 'PiB',
            '6' => 'EiB',
            '7' => 'ZiB',
            '8' => 'YiB'
        );

        for($i = 0; $size >= 1024 && $i <= count($unit); $i++){
            $size = $size/1024;
        }

        return round($size, $decimals).' '.$unit[$i];
    }
}

if (! function_exists('formatBytes2')) {
    function formatBytes2($size, $decimals = 0){
        $unit = array(
            '0' => 'Byte',
            '1' => 'KB',
            '2' => 'MB',
            '3' => 'GB',
            '4' => 'TB',
            '5' => 'PB',
            '6' => 'EB',
            '7' => 'ZB',
            '8' => 'YB'
        );

        for($i = 0; $size >= 1000 && $i <= count($unit); $i++){
            $size = $size/1000;
        }

        return round($size, $decimals).''.$unit[$i];
    }
}

if (! function_exists('formatBites')) {
    function formatBites($size, $decimals = 0){
        $unit = array(
            '0' => 'bps',
            '1' => 'kbps',
            '2' => 'Mbps',
            '3' => 'Gbps',
            '4' => 'Tbps',
            '5' => 'Pbps',
            '6' => 'Ebps',
            '7' => 'Zbps',
            '8' => 'Ybps'
        );

        for($i = 0; $size >= 1000 && $i <= count($unit); $i++){
            $size = $size/1000;
        }

        return round($size, $decimals).' '.$unit[$i];
    }
}

if (! function_exists('formatDTM')) {
    function formatDTM($dtm) {
        if (substr($dtm, 1,1) == "d" || substr($dtm, 2,1) == "d") {
            $day = explode("d",$dtm)[0]."d";
            $day = str_replace("d", "d ", str_replace("w", "w ", $day));
            $dtm = explode("d",$dtm)[1];
        } elseif (substr($dtm, 1,1) == "w" && substr($dtm, 3,1) == "d" || substr($dtm, 2,1) == "w" && substr($dtm, 4,1) == "d") {
            $day = explode("d",$dtm)[0]."d";
            $day = str_replace("d", "d ", str_replace("w", "w ", $day));
            $dtm = explode("d",$dtm)[1];
        } elseif (substr($dtm, 1,1) == "w" || substr($dtm, 2,1) == "w" ) {
            $day = explode("w",$dtm)[0]."w";
            $day = str_replace("d", "d ", str_replace("w", "w ", $day));
            $dtm = explode("w",$dtm)[1];
        } else {
            $day = null;
        }

        // secs
        if (strlen($dtm) == "2" && substr($dtm, -1) == "s") {
            $format = $day." 00:00:0".substr($dtm, 0,-1);
        } elseif (strlen($dtm) == "3" && substr($dtm, -1) == "s") {
            $format = $day." 00:00:".substr($dtm, 0,-1);
        //minutes
        } elseif (strlen($dtm) == "2" && substr($dtm, -1) == "m") {
            $format = $day." 00:0".substr($dtm, 0,-1).":00";
        } elseif (strlen($dtm) == "3" && substr($dtm, -1) == "m") {
            $format = $day." 00:".substr($dtm, 0,-1).":00";
        //hours
        } elseif (strlen($dtm) == "2" && substr($dtm, -1) == "h") {
            $format = $day." 0".substr($dtm, 0,-1).":00:00";
        } elseif (strlen($dtm) == "3" && substr($dtm, -1) == "h") {
            $format = $day." ".substr($dtm, 0,-1).":00:00";
        
        //minutes -secs
        } elseif (strlen($dtm) == "4" && substr($dtm, -1) == "s" && substr($dtm,1,-2) == "m") {
            $format = $day." "."00:0".substr($dtm, 0,1).":0".substr($dtm, 2,-1);
        } elseif (strlen($dtm) == "5" && substr($dtm, -1) == "s" && substr($dtm,1,-3) == "m") {
            $format = $day." "."00:0".substr($dtm, 0,1).":".substr($dtm, 2,-1);
        } elseif (strlen($dtm) == "5" && substr($dtm, -1) == "s" && substr($dtm,2,-2) == "m") {
            $format = $day." "."00:".substr($dtm, 0,2).":0".substr($dtm, 3,-1);
        } elseif (strlen($dtm) == "6" && substr($dtm, -1) == "s" && substr($dtm,2,-3) == "m") {
            $format = $day." "."00:".substr($dtm, 0,2).":".substr($dtm, 3,-1);

        //hours -secs
        } elseif (strlen($dtm) == "4" && substr($dtm, -1) == "s" && substr($dtm,1,-2) == "h") {
            $format = $day." 0".substr($dtm, 0,1).":00:0".substr($dtm, 2,-1);
        } elseif (strlen($dtm) == "5" && substr($dtm, -1) == "s" && substr($dtm,1,-3) == "h") {
            $format = $day." 0".substr($dtm, 0,1).":00:".substr($dtm, 2,-1);
        } elseif (strlen($dtm) == "5" && substr($dtm, -1) == "s" && substr($dtm,2,-2) == "h") {
            $format = $day." ".substr($dtm, 0,2).":00:0".substr($dtm, 3,-1);
        } elseif (strlen($dtm) == "6" && substr($dtm, -1) == "s" && substr($dtm,2,-3) == "h") {
            $format = $day." ".substr($dtm, 0,2).":00:".substr($dtm, 3,-1);

        //hours -secs
        } elseif (strlen($dtm) == "4" && substr($dtm, -1) == "m" && substr($dtm,1,-2) == "h") {
            $format = $day." 0".substr($dtm, 0,1).":0".substr($dtm, 2,-1).":00";
        } elseif (strlen($dtm) == "5" && substr($dtm, -1) == "m" && substr($dtm,1,-3) == "h") {
            $format = $day." 0".substr($dtm, 0,1).":".substr($dtm, 2,-1).":00";
        } elseif (strlen($dtm) == "5" && substr($dtm, -1) == "m" && substr($dtm,2,-2) == "h") {
            $format = $day." ".substr($dtm, 0,2).":0".substr($dtm, 3,-1).":00";
        } elseif (strlen($dtm) == "6" && substr($dtm, -1) == "m" && substr($dtm,2,-3) == "h") {
            $format = $day." ".substr($dtm, 0,2).":".substr($dtm, 3,-1).":00";

        //hours minutes secs
        } elseif (strlen($dtm) == "6" && substr($dtm, -1) == "s" && substr($dtm,3,-2) == "m" && substr($dtm,1,-4) == "h") {
            $format = $day." 0".substr($dtm, 0,1).":0".substr($dtm, 2,-3).":0".substr($dtm, 4,-1);
        } elseif (strlen($dtm) == "7" && substr($dtm, -1) == "s" && substr($dtm,3,-3) == "m" && substr($dtm,1,-5) == "h") {
            $format = $day." 0".substr($dtm, 0,1).":0".substr($dtm, 2,-4).":".substr($dtm, 4,-1);
        } elseif (strlen($dtm) == "7" && substr($dtm, -1) == "s" && substr($dtm,4,-2) == "m" && substr($dtm,1,-5) == "h") {
            $format = $day." 0".substr($dtm, 0,1).":".substr($dtm, 2,-3).":0".substr($dtm, 5,-1);
        } elseif (strlen($dtm) == "8" && substr($dtm, -1) == "s" && substr($dtm,4,-3) == "m" && substr($dtm,1,-6) == "h") {
            $format = $day." 0".substr($dtm, 0,1).":".substr($dtm, 2,-4).":".substr($dtm, 5,-1);
        } elseif (strlen($dtm) == "7" && substr($dtm, -1) == "s" && substr($dtm,4,-2) == "m" && substr($dtm,2,-4) == "h") {
            $format = $day." ".substr($dtm, 0,2).":0".substr($dtm, 3,-3).":0".substr($dtm, 5,-1);
        } elseif (strlen($dtm) == "8" && substr($dtm, -1) == "s" && substr($dtm,4,-3) == "m" && substr($dtm,2,-5) == "h") {
            $format = $day." ".substr($dtm, 0,2).":0".substr($dtm, 3,-4).":".substr($dtm, 5,-1);
        } elseif (strlen($dtm) == "8" && substr($dtm, -1) == "s" && substr($dtm,5,-2) == "m" && substr($dtm,2,-5) == "h") {
            $format = $day." ".substr($dtm, 0,2).":".substr($dtm, 3,-3).":0".substr($dtm, 6,-1);
        } elseif (strlen($dtm) == "9" && substr($dtm, -1) == "s" && substr($dtm,5,-3) == "m" && substr($dtm,2,-6) == "h") {
            $format = $day." ".substr($dtm, 0,2).":".substr($dtm, 3,-4).":".substr($dtm, 6,-1);

        } else {
            $format = $dtm;
        }

        return $format;
    }
}

if (! function_exists('format_date')) {
    function format_date($dtm) {
        if (str_contains($dtm, 'd')) {
            preg_match('/(?P<days>\d+)d(?P<hours>\d+)h(?P<minutes>\d+)m(?P<seconds>\d+)s/', $dtm, $matches);
            $daysText = (isset($matches['days']) ? $matches['days'] . 'day' : '0day') . ' ';
        } else {
            if (!str_contains($dtm, 'h')) {
                $dtm = '0h' . $dtm;
            }
            if (!str_contains($dtm, 's')) {
                $dtm = $dtm . '0s';
            }
            preg_match('/(?P<hours>\d+)h(?P<minutes>\d+)m(?P<seconds>\d+)s/', $dtm, $matches);
            $daysText = '';
        }

        Log::info('date', [$dtm, $matches]);
        
        $timeText = sprintf('%02d:%02d:%02d', $matches['hours'], $matches['minutes'], $matches['seconds']);
        // Combine the results
        return $daysText . $timeText;
    }
}