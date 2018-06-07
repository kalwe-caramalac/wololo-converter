<?php

class DirList
{

    public function listDir($path = "", $fflag = 0) {
        $result = [];
        $files = scan($path);
    }
}


function getDirContents($dir){
    $results = array();
    $files = scandir($dir);

        foreach($files as $key => $value){
            if(!is_dir($dir. DIRECTORY_SEPARATOR .$value)){
                $results[] = $value;
            } else if(is_dir($dir. DIRECTORY_SEPARATOR .$value)) {
                $results[] = $value;
                getDirContents($dir. DIRECTORY_SEPARATOR .$value);
            }
        }
}
