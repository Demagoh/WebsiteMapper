<?php

/**
 * A CLI website mapper utility.
 * 
 * @author  Demagoh     https://github.com/Demagoh
 * @version 1.0 - WIP
 */

/// Set up start variables
if ($argc != 2) {
    die("Only a website mapper configuration file must be provided.\n");
}

$mapperConfigFile = $argv[1];   // name of config file in website mapper's directory



/// Get the website mapper configuration
$configurationLength = filesize($mapperConfigFile);
$mapperConfigFile = fopen($mapperConfigFile, "r");
$configuration = json_decode(fread($mapperConfigFile, $configurationLength), true);

$websiteRootPath = $configuration["websiteRootSystemPath"];     // path to the website's root
$pathsToIgnore = [];                                            // the website paths to ignore
$dirsToGeneralize = [];                                         // the website paths to generalize
$indexesToCull = [];                                            // the website paths to turn into /

/**
 * Function to convert a website mapper $configuration array to one that can be used by the mapper.
 * 
 * @param   string      $arrayToConvert     The index name of the array in the website mapper
 *                                          $configuration to be converted.
 * @return  array                           Returns the converted array.
 */
function convertToWorkableArray($arrayToConvert){
    global $configuration;

    $tempArray = [];    // we need this index array in order to make Intelliphense happy
    $finalArray = [];   // associative array we'll return at the end of this function

    if (isset($configuration[$arrayToConvert])) {
        // we only allow unique paths to be added to the final array
        for ($i = 0; $i < count($configuration[$arrayToConvert]); $i++) {
            if (!in_array($configuration[$arrayToConvert], $tempArray)) {
                $tempArray[] = $configuration[$arrayToConvert][$i];
            }
        }
    
        // translate the values in the temporary array to their finalised forms in the final array
        foreach ($tempArray as $path) {
            $finalArray[] = ["path" => $path, "found" => false];
        }
    }
    
    return $finalArray;
}

$pathsToIgnore = convertToWorkableArray("websitePathsToIgnore");
$dirsToGeneralize = convertToWorkableArray("websiteDirectoriesToGeneralize");
$indexesToCull = convertToWorkableArray("indexFilesToCull");



/// Clean up and validate entries

// remove unnecessary whitespaces
$websiteRootPath = trim($websiteRootPath);

// remove unnecessary / characters from the beginning of the path
while (strlen($websiteRootPath) > 1 && $websiteRootPath[0] == "/") {
    $websiteRootPath = substr($websiteRootPath, 1);
}

// validate the partially cleaned up path
if (trim($websiteRootPath) == "/") {
    die("The website mapper will not scan from the system root directory.\n");
} else if (trim($websiteRootPath) == "") {
    die("Please provide a valid system path to the website's root directory.\n");
}

// remove unneccessary / characters from the end of the path
while ($websiteRootPath[strlen($websiteRootPath)-1] == "/") {
    $websiteRootPath = substr($websiteRootPath, 0, -1);
}

$websiteRootPath = "/" .trim($websiteRootPath);

while (str_contains($websiteRootPath, "//")) {
    $websiteRootPath = str_replace("//", "/", $websiteRootPath);
}

// validate the fully cleaned up path
if ($websiteRootPath[0] != "/") {
    die("The path to the website's root directory must be an absolute system path.\n");
}

// clean up and validate website paths
/**
 * Function to clean up and validate all the paths in an array.
 * 
 * @param   array       $paths      The array of paths to clean up.
 * @param   string      $type       What type of paths is beeing cleaned up
 *                                  (ignore, generalize, cull).
 * @return  null                    Doesn't return anything, the $paths array is passed through
 *                                  a reference.
 */
function cleanUpAndValidatePaths(&$paths, $type) {
    global $websiteRootPath;

    foreach ($paths as &$pathToClean) {
        // remove unnecessary whitespaces
        $pathToClean["path"] = trim($pathToClean["path"]);
    
        // remove unnecessary / characters from the beginning of the path
        while (strlen($pathToClean["path"]) > 0 && $pathToClean["path"][0] == "/") {
            $pathToClean["path"] = substr($pathToClean["path"], 1);
        }
    
        // validate the partially cleaned up path
        if (trim($pathToClean["path"]) == "") {
            die("Please provide a valid website path for the website mapper to " .$type .".\n");
        }
    
        // remove unneccessary / characters from the end of the path
        while ($pathToClean["path"][strlen($pathToClean["path"])-1] == "/") {
            $pathToClean["path"] = substr($pathToClean["path"], 0, -1);
        }
    
        // validate the almost fully cleaned up path
        if (str_replace(".", "", $pathToClean["path"]) == "") {
            die("Cannot " .$type ." the current (\".\") nor the parent (\"..\") directories.\n");
        }
    
        $pathToClean["path"] = "/" .trim($pathToClean["path"]);
    
        while (str_contains($pathToClean["path"], "//")) {
            $pathToClean["path"] = str_replace("//", "/", $pathToClean["path"]);
        }
    
        // validate the fully cleaned up path
        if (trim($pathToClean["path"]) == "/") {
            die("The website mapper cannot " .$type ." the website's root directory.\n");
        }
    
        if ($type != "cull") {
            $pathToClean["path"] = $websiteRootPath .$pathToClean["path"];
        }
    }
}

cleanUpAndValidatePaths($pathsToIgnore, "ignore");
cleanUpAndValidatePaths($dirsToGeneralize, "generalize");
cleanUpAndValidatePaths($indexesToCull, "cull");



/// Let the user know what we're mapping and validate that they agree with it
$feedback = "The website mapper will map from system path \e[0;33m" .$websiteRootPath
    ."\e[m.\nIt will \e[0;31mignore\e[m " .((count($pathsToIgnore) > 0) ?
    "the following system paths:\n" : "no specific system paths, ");

for ($i = 0; $i < count($pathsToIgnore); $i++) {
    $feedback .= " - \e[0;33m" .$pathsToIgnore[$i]["path"] ."\e[m,\n";
}

$feedback .= "while \e[0;31mgeneralizing\e[m (only providing the directory \e[0;33mwithout\e[m ".
    "its children) " .((count($dirsToGeneralize) > 0) ? "the following system paths:\n" :
    "no specific system paths ");

for ($i = 0; $i < count($dirsToGeneralize); $i++) {
    $feedback .= " - \e[0;33m" .$dirsToGeneralize[$i]["path"] ."\e[m"
        .(($i+1 == count($dirsToGeneralize)) ? "" : ",") ."\n";
}

$feedback .= "and \e[0;31mculling\e[m (translating names from \e[0;33m\"[path]/[name]\"\e[m "
    ."to \e[0;33m\"[path]/\"\e[m) " .((count($indexesToCull) > 0) ?
    "the following index files' names:\n" : "no index files's names.\n");

for ($i = 0; $i < count($indexesToCull); $i++) {
    $feedback .= " - \e[0;33m" .$indexesToCull[$i]["path"] ."\e[m"
        .(($i+1 == count($indexesToCull)) ? "." : ",") ."\n";
}

$feedback .= "\nDo you wish to proceed? (y/n) [y] ";

echo $feedback;

$console = fopen("php://stdin", "r");
$input = fgets($console);
$input = trim($input);
$continue = false;

if (strlen($input) == 0 || (strlen($input) > 0 && $input[0] == "y")) {
    $continue = true;
}

if (!$continue) {
    die("\nQuitting the website mapper.\n");
}



/// Map the website
/**
 * Recursive function to scan through a directory.
 * 
 * @param   string      $directoryPath      Specifies the absolute system path to the directory
 *                                          we're currently scanning.
 * @return  array                           Returns an array with 3 index arrays, each with either
 *                                          all or only the specific subdirectories and files in
 *                                          the current directory, which the mapper is allowed
 *                                          to map.
 */
function exploreDirectory($directoryPath) {
    global $websiteRootPath;
    global $pathsToIgnore;
    global $dirsToGeneralize;
    global $indexesToCull;

    $currentDirectory = scandir($directoryPath);
    unset($currentDirectory[0]);    // remove the current directory (".") reference
    unset($currentDirectory[1]);    // remove the parent directory ("..") reference

    $map = [
        "all" => [],
        "generalized" => [],
        "culled" => []
    ];

    foreach ($currentDirectory as $elementInDirectory) {
        $elementInDirectory = $directoryPath ."/" .$elementInDirectory;
        
        $shouldBeIgnored = false;

        foreach ($pathsToIgnore as $pathToIgnore) {
            if ($pathToIgnore["path"] == $elementInDirectory) {
                $shouldBeIgnored = true;
                break;
            }
        }

        if (!$shouldBeIgnored) {
            $shouldBeGeneralized = false;

            if (is_dir($elementInDirectory)) {
                foreach ($dirsToGeneralize as $dirToGeneralize) {
                    if ($dirToGeneralize["path"] == $elementInDirectory) {
                        $shouldBeGeneralized = true;
                        break;
                    }
                }
            }

            if ($shouldBeGeneralized) {
                $map["all"][] = str_replace($websiteRootPath, "", $elementInDirectory);
                $map["generalized"][] = str_replace($websiteRootPath, "",
                    $elementInDirectory);
            } else {
                $shouldBeCulled = false;

                if (!is_dir($elementInDirectory)) {
                    foreach ($indexesToCull as $indexToCull) {
                        if ($indexToCull["path"] == str_replace($websiteRootPath, "",
                            $elementInDirectory)) {
                            $shouldBeCulled = true;
                            break;
                        }
                    }
                }

                if ($shouldBeCulled) {
                    $map["all"][] = $directoryPath ."/";
                    $map["culled"][] = $directoryPath ."/";
                } else {
                    $map["all"][] = str_replace($websiteRootPath, "", $elementInDirectory);
                    
                    if (is_dir($elementInDirectory)) {
                        $tempMap = exploreDirectory($elementInDirectory);
                        
                        foreach ($tempMap["all"] as $tempPath) {
                            $map["all"][] = $tempPath;
                        }
                        
                        foreach ($tempMap["generalized"] as $tempPath) {
                            $map["generalized"][] = $tempPath;
                        }
                        
                        foreach ($tempMap["culled"] as $tempPath) {
                            $map["culled"][] = $tempPath;
                        }
                    }
                }
    
            }
        }
    }

    return $map;
}

$websiteMap = exploreDirectory($websiteRootPath);



/// Save website map
$feedback = "Your website map has been generated, do you want it to be written to a file:\n";
$feedback .= "\e[0;33m 1) as plain text\e[m\n";
$feedback .= "\e[0;33m 2) in the form of a Cloudflare rule expression\e[m\n";
$feedback .= "\e[0;33m 3) in the form of a Cloudflare rule expression, but without the .html and"
    ." .php file extensions\e[m\n";
$feedback .= "(1, 2, 3) [1] ";

echo $feedback;

$input = fgets($console);
$input = trim($input);
$option = 0;

// figure out the format in which to save the website map
$option = (strlen($input) == 0 || (strlen($input) > 0 && $input == "1"));

if ($option != 1) {
    switch ($input) {
        case "2":
            $option = 2;
            break;
        case "3":
            $option = 3;
            break;
        default:
            $option = 1;
    }
}

$file = fopen("websitemap.txt", "w");
$fileContents = "";

switch ($option) {
    case 2:     // Cloudflare rule expression
        if (count($websiteMap["all"]) != count($websiteMap["generalized"])) {
            foreach ($websiteMap["all"] as $websitePath) {
                if (!in_array($websitePath, $websiteMap["generalized"])) {
                    if (strlen($fileContents) != 0) {
                        $fileContents .= " ";
                    }

                    $fileContents .= "\"" .$websitePath ."\"";
                }
            }

            $fileContents = "(not http.request.uri.path in {" .$fileContents ."}"
                .((count($websiteMap["generalized"]) > 0) ? " and " : ")");
        }

        if (count($websiteMap["generalized"]) > 0) {
            $counter = 0;

            foreach ($websiteMap["generalized"] as $websitePath) {
                if ($counter != 0) {
                    $fileContents .= " and ";
                }

                $counter++;

                $fileContents .= "not starts_with(http.request.uri.path, \"" .$websitePath ."\")"; 
            }

            $fileContents .= ")";
        }

        break;
    case 3:     // Cloudflare rule expression (remove .html or .php)
        if (count($websiteMap["all"]) != count($websiteMap["generalized"])) {
            foreach ($websiteMap["all"] as $websitePath) {
                if (!in_array($websitePath, $websiteMap["generalized"])) {
                    if (strlen($fileContents) != 0) {
                        $fileContents .= " ";
                    }

                    $fileContents .= "\"" .str_replace(".html", "", str_replace(".php", "",
                        $websitePath)) ."\"";
                }
            }

            // add the website root
            $fileContents .= ((strlen($fileContents) > 0) ? " \"/\"" : "\"/\"");

            $fileContents = "(not http.request.uri.path in {" .$fileContents ."}"
                .((count($websiteMap["generalized"]) > 0) ? " and " : ")");
        }

        if (count($websiteMap["generalized"]) > 0) {
            $counter = 0;

            foreach ($websiteMap["generalized"] as $websitePath) {
                if ($counter != 0) {
                    $fileContents .= " and ";
                }

                $counter++;

                $fileContents .= "not starts_with(http.request.uri.path, \"" .$websitePath ."\")"; 
            }

            $fileContents .= ")";
        }

        break;
    default:    // plain text
        foreach ($websiteMap["all"] as $websitePath) {
            $fileContents .= $websitePath ."\n";
        }
}

fwrite($file, $fileContents);

?>