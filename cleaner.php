<?php

/*
 * ========================================================================
 *
 * This script is created in order to help you clean viruses from the files on your FTP.
 *
 * When your website is infected, sometimes it's easy to create a regular expression to
 * cut virus body from your files. This script allows you to process all files on
 * your FTP and use regular expressions to cut virus from your files.
 *
 * ------------------------------------------------------------------------
 *
 * Copyright (c) 2011 Vladislav "FractalizeR" Rastrusny
 * Website: http://www.fractalizer.ru
 * Email: FractalizeR@yandex.ru
 *
 * ------------------------------------------------------------------------
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================================
 */


//=====================================================================================================================
// Settings
//=====================================================================================================================

//TODO: change these settings to match a virus you need to clean

// Virus body start and end regular expressions
$virusBodyStart = preg_quote('=3-1;i=c-2;if(window.document)if(parseInt');
$virusBodyEnd   = preg_quote('if(f)z=s;e(z);');
$virusBodyRegEx = "/$virusBodyStart.+?$virusBodyEnd/is";

//Which file types we need to process
$fileNameMasks = array('*.js');

//Files of what age we need to process (Unix time)
$fileAge = array(0, PHP_INT_MAX);

//A list of folder which we need to process
$foldersToProcess = array('/home/abcland/public_html', '/home/abcland/public_html2');

//=====================================================================================================================
// Script
//=====================================================================================================================

//Processing folders
$timeStarted = time();
foreach ($foldersToProcess as $folderToProcess) {
    processPath($folderToProcess, $virusBodyRegEx, $fileNameMasks, $fileAge);
}
reportInformation("");
reportInformation('Processing finished. Total time taken is ' . (time() - $timeStarted) . ' seconds.', true);
reportInformation("");

/**
 * Path processing routine
 *
 * @param string $folderToProcess
 * @param string $virusRegEx
 * @param array  $fileNameMasks
 *
 * @param array  $fileAge
 *
 * @internal param \RecursiveIteratorIterator $iterator
 */
function processPath($folderToProcess, $virusRegEx, array $fileNameMasks, array $fileAge) {
    $virusBodiesCured = 0;
    $filesProcessed   = 0;
    $errors           = 0;
    $timeStarted      = time();

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderToProcess));
    list($fileMinAge, $fileMaxAge) = $fileAge;

    foreach ($iterator as $fileInfo) {
        /** @var $fileInfo SplFileInfo  */
        if ($fileInfo->isDir()) {
            continue;
        }

        //Checking file modification time
        $fileModificationTime = $fileInfo->getMTime();
        if ($fileModificationTime < $fileMinAge or $fileModificationTime > $fileMaxAge) {
            continue;
        }

        //Checking file masks
        $needToProcess = false;
        foreach ($fileNameMasks as $fileNameMask) {
            if (fnmatch($fileNameMask, $fileInfo->getFilename())) {
                $needToProcess = true;
            }
        }

        //If we need to process this file
        if (!$needToProcess) {
            continue;
        }

        //Trying to cure
        try {
            $virusBodiesCured += processFile($fileInfo, $virusRegEx);
            $filesProcessed++;
        }
        catch (Exception $e) {
            reportException($e);
            $errors++;
        }
    }

    //Reporting statistics
    reportInformation("");
    reportInformation("Processing of folder '$folderToProcess' finished. $errors errors, $filesProcessed files processed, $virusBodiesCured virus bodies cured.",
                      true);
    reportInformation("Time taken is " . (time() - $timeStarted) . " seconds", true);
    reportInformation("");
}

/**
 * File processing routine
 *
 * @param SplFileInfo $fileInfo
 * @param string      $virusRegEx
 *
 * @throws Exception
 * @return int Virus bodies cured
 */
function processFile($fileInfo, $virusRegEx) {
    $filePath = $fileInfo->getPathname();
    if (!is_readable($filePath)) {
        throw new Exception("File '$filePath' is not readable! Permission problem?");
    }

    if (!is_writable($filePath)) {
        throw new Exception("File '$filePath' is not writable! Permission problem?");
    }

    $fileContents = file_get_contents($filePath);
    $fileContents = preg_replace($virusRegEx, '', $fileContents, -1, $replacementsDone);
    file_put_contents($filePath, $fileContents);
    reportInformation("Processed file: '$filePath' ($replacementsDone)");
    return $replacementsDone;
}

/**
 * Reporting exception
 *
 * @param Exception $e
 */
function reportException(Exception $e) {
    echo('<b style="color: red;">' . $e->getMessage() . "</b><br />\r\n");
}

/**
 * Reporting information
 *
 * @param string $msg
 * @param bool   $emphasis
 */
function reportInformation($msg, $emphasis = false) {
    if ($emphasis) {
        echo("<b>" . htmlentities($msg) . "</b><br />\r\n");
    } else {
        echo(htmlentities($msg) . "<br />\r\n");
    }
}
