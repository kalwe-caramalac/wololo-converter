<?php

function loadDSN() {
    $path_to_configinc = "";

    if (!is_files($path_to_configinc)) {
        echo "Config.inc not found";
     } else {
        if ($handle = fopen($path_to_configinc, 'r')) {
            while (!feof($handle)) {
                $line = fgets($handle);
                if (preg_match("//", $line)) {
                    // statments
                }
            }
        }
    }
}
