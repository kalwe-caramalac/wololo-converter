<?php

// namespace Core\ConversorUTF8;

// use Core\Conversor\Conversor;
// include __DIR__ . "/Conversor.php";

class ConversorUTF8
{
    const DEFOUTPUT_PATH    = "converted_code";
    const DEFOUT_ENCODE     = "UTF-8";
    const DEFIN_ENCODE      = "ISO-8859-1";

    private $inputPath;
    private $outputPath;
    private $encodeType;

    private $io;
    private $args;

    public function __construct($args) {
        $args = (object)$args;
        $this->io = $args->io;
        $this->inputPath = $args->input_path;
        $this->outputPath = $args->output_path;
        $this->encodeType = $args->encode_type;
    }

    private function trimLastSlash(&$str) {
        $lastPos = strlen($str) - 1;
        $tmp = substr($str, $lastPos);
        if ($tmp == "/")
            $str = substr_replace($str, "", $lastPos);
    }

    public function preparaConversao() {

        $this->io->section("Preparing to conversion...");
        $this->io->text("Ajust input/ouput paths...");

        $this->trimLastSlash($this->inputPath);
        $this->trimLastSlash($this->outputPath);

        if (!is_dir($this->inputPath)) {
            $this->io->text("ERROR - Input path is not a directory.");
            exit(0);
        }

        $this->outputPath .= DIRECTORY_SEPARATOR . static::DEFOUTPUT_PATH;

        $this->io->text("Done!");
    }

    private function ajustPath($path) {
        $lastPos = strlen($this->inputPath);
        $tailPath = substr($path, $lastPos);

        $newOutputPath = $this->outputPath . $tailPath;

        return $newOutputPath;
    }

    private function arrangeOutput() {

        $this->io->section("Arrange Output");

        $path = $this->outputPath;
        if (is_dir($path))
            `rm -rf {$path}`; # remove trash

        if (!is_dir($path)) {
            if (mkdir($path, 0777, TRUE)) {
                chdir($path);
                system("git init > /dev/null");
            } else {
                $this->io->text("ERROR - Canno`t create output directory.");
                exit(0);
            }
        }

        $this->io->text("Output directory created: '{$path}'.");
        $this->io->text("Git repository create.");

        return is_writable($path);
    }

    private function cloneDirectoryTree($path) {

        if (is_dir($path))
            $tree = scandir($path); # ls
        else {
            $this->io->text("ERROR - Input direcotry already exists.");
            exit(0);
        }

        // FIXME: 'docker' ta removendo o 'script/docker' tbm...eu n kero isso
        // ps: alterar para data funciona, porem exclui em '/_sys/classes/libs/PHPExcel/Examples/data'
        $foldersToExclude = [".", "..", ".git", ".vscode", "vendor", "dumps",
                           "data", ".sass-cache", "nbproject", "vivo"];

        if ($tree) {
            $tree = array_diff($tree, $foldersToExclude);

            foreach ($tree as $key => $lsitem) {

                $currentPath = $path . DIRECTORY_SEPARATOR . $lsitem;
                if (is_dir($currentPath)) {

                    $path2create = $this->ajustPath($path) . DIRECTORY_SEPARATOR . $lsitem;
                    if (!is_dir($path2create))
                        mkdir($path2create, 0777, TRUE);

                    $this->cloneDirectoryTree($currentPath);
                }
            }
        }
    }

    private function changeCharsetEncode($line, $fromEncode = "iso-8859-1", $toEncodeType = "UTF-8") {
        $pattern = "/charset=/i";
        if (preg_match_all($pattern, $line))
            $line = preg_replace("/{$fromEncode}/i", $toEncodeType, $line);

        return $line;
    }

    private function removeUtf8EncodeDecode(String $line) {

        $pattern = "/utf8_(encode|decode)\(/";
        $pregRes = preg_match_all($pattern, $line);

        if ($pregRes == 1) {

            $pttr = "/utf8_(encode|decode)\(((.)+)\)/i";

            preg_match($pttr, $line, $matches);
            $matches[2] = preg_replace("/(?<=[\"\]\)])\)(?=(\.))/", "", $matches[2], 1);

            $line = preg_replace($pttr, $matches[2], $line);

            if (preg_match("/(?<=(\.\")) <\/p>$/", $line)) {
                $tmp = str_replace(".\" <", "\") <", $line);
                $line = $tmp;
            } else if (preg_match("/json_(decode|encode)/", $line)) {
                $tmp = preg_replace("/\)/", "", $line, 1);
                $tmp = str_replace(";", ");", $tmp);
                $line = $tmp;
            } else if (preg_match("/(reg\['label'\]\))|(label\))/", $line)) {
                $tmp = preg_replace("/\)/", "", $line, 1);
                $tmp = str_replace("',', '.'", "',', '.')", $tmp);
                $line = $tmp;
            } else if (preg_match('/signXML\(\$xml\)/', $line)) {
                $tmp = preg_replace("/'; /", "'); ", $line, 1);
                $tmp = preg_replace("/xml\),/", "xml, ", $tmp, 1);
                $line = $tmp;
            } else if (preg_match('/\(\)\[\"msg\"\]\)/', $line)) {
                $tmp = preg_replace("/msg\"\]\) /", "msg\"] ", $line, 1);
                $tmp = preg_replace('/>\";/', ">\");", $tmp, 1);
                $line = $tmp;
            } else if (preg_match('/2012<\/td>/', $line)) {
                $tmp = preg_replace("/2012<\/td>/", "2012)</td>", $line);
                $line = $tmp;
            } else if(preg_match("/KEY_API_MAPS/", $line)) {
                $tmp = preg_replace('/<\?\=  \$json\)\?>/', "<?= \$json ?>", $line, 1);
                $tmp = preg_replace("/\?>';/", "?>');", $tmp, 1);
                $line = $tmp;
            } else if(preg_match("/#msgToken/", $line)) {
                $tmp = preg_replace('/\."\';";/', '."\');";', $line, 1);
                $line = $tmp;
            } else if (preg_match('/QRcode\(\$msg/', $line)) {
                $tmp = preg_replace('/msg\), \$err;/', 'msg, $err);', $line, 1);
                $line = $tmp;
            } else if (preg_match('/\$d\[\$key\)\]/', $line)) {
                $tmp = preg_replace("/\)/", "", $line, 1);
                $tmp = preg_replace("/;/", ");", $tmp, 1);
                $line = $tmp;
            }

            // $line .= " // #ALTERED#";
            // echo "flag #ALTERED#: " . $line . "\n"; # em teste...
            // echo "line 1A: " . $line . "\n"; # debug only

        } else if ($pregRes >= 2) {

            $pttr = "/utf8_(encode|decode)\(((.)+)\)/i";
            preg_match($pttr, $line, $matches); # return '$matches' for work within

            $pttr2 = "/utf8_(encode|decode)\(/i";
            $matches[2] = preg_replace($pttr2, "", $matches[2], 1);
            $matches[2] = preg_replace("/\)/", "", $this->removeUtf8EncodeDecode($matches[2]), 1);

            $line = preg_replace($pttr, $matches[2], $line);
        }

        # isso funciona mas com limitacoes
        // $pattern = "/utf8_(encode|decode)/";
        // if (preg_match_all($pattern, $line)) {
        //     $line = preg_replace($pattern, "", $line);
        // }

        return $line;
    }

    private function removeArrayIsoToUtf8Calls(String $line) {

        $pattern = "/ArrayIsoToUtf8::(converter|decodificar|trataUnicode)\(/";
        $pregRes = preg_match_all($pattern, $line);

        if ($pregRes == 1) {

            $pttr = "/ArrayIsoToUtf8::(converter|decodificar|trataUnicode)\(((.)+)\)(\?>,\s(false|true)\);)?/";
            preg_match($pttr, $line, $matches);

            $line = preg_replace($pttr, $matches[2], $line);

            if (preg_match("/\?>,\s(true|false);/", $line) || /* ?>,\s(true|false);$ */
                preg_match("/this->getChave\(\);$/", $line) ||
                preg_match("/JSON_UNESCAPED_UNICODE;/", $line))
            {
                $tmp = str_replace(';', ");", $line);
                $line = preg_replace("/\)/", "", $tmp, 1);
            }
            if (preg_match("/registraLogAcessoComunicado/", $line)) {
                $tmp = preg_replace("/\(->/", "()->", $line, 1);
                $tmp = preg_replace("/\),/", ",", $tmp, 1);
                $line = $tmp;
            }
        } else if ($pregRes >= 2) {

            $pttr = "/ArrayIsoToUtf8::(converter|decodificar|trataUnicode)\(((.)+)\)/";
            $pttr2 = "/ArrayIsoToUtf8::(converter|decodificar|trataUnicode)\(/";

            preg_match($pttr, $line, $matches); # return '$matches' for work within

            $matches[2] = preg_replace($pttr2, "", $matches[2], 1);
            $matches[2] = preg_replace("/\)/", "", $this->removeArrayIsoToUtf8Calls($matches[2]), 1);

            $line = preg_replace($pttr, $matches[2], $line);
        }
        return $line;
    }

    private function treat_mb_convert_encoding(String $line) {

        # VALIDAR: o uso da func mb_convert_encoding

        $pattern = "/mb_convert_encoding\(/";
        $pregRes = preg_match_all($pattern, $line);

        if ($pregRes == 1) {
            echo "mb_converter " . $line;
        }
    }

    private function changeWrongEncode($line) {}

    private function applyChangesInLine(String $line) {
        if ($line) {
            $line = $this->changeCharsetEncode($line);
            $line = $this->removeUtf8EncodeDecode($line);
            $line = $this->removeArrayIsoToUtf8Calls($line);
            $line = $this->changeWrongEncode($line);
            // $line = $this->treat_mb_convert_encoding($line);
        }
        return $line;
    }

    private function spitoutConvertedFile(String $fileName, String $data) {

        $outputFilePath = $this->ajustPath($fileName);
        if ($fileToWrite = fopen($outputFilePath, 'a')) {
            if (is_writable($outputFilePath)) {
                // fwrite($fileToWrite, utf8_encode($data)); # magic
                fwrite($fileToWrite, mb_convert_encoding($data, self::DEFOUT_ENCODE, self::DEFIN_ENCODE));
            }

            fclose($fileToWrite); # FIXME: da pra otimiza essa abertura e fexamento de arquivo
        }
    }

    private function excludeFolders($folders) {
        $foldersToExclude = [".", "..", ".git", ".vscode", "vendor", "dumps", "vivo",
                             "data", ".sass-cache", "nbproject", "xsd"];

        $folders = array_diff($folders, $foldersToExclude);

        return $folders;
    }

    private function processLine($pathToFileName) {

        $patternToIgnore = "/\.(jpg|jpeg|png|gif|svg|woff|woff2|eot|ttf)/";
        $pregRes = preg_match_all($patternToIgnore, $pathToFileName);
        if ($pregRes == 0) {
            // echo $pathToFileName . "\n";
            if (is_readable($pathToFileName)) {
                if ($file = fopen($pathToFileName, 'r')) {
                    while (!feof($file)) {
                        $line = fgets($file);
                        $line = $this->applyChangesInLine($line);
                        $this->spitoutConvertedFile($pathToFileName, $line);
                    }
                    fclose($file);
                }
            }
        }
    }

    private function processFiles($path) {

        if (is_dir($path))
            $tree = scandir($path);
        else {
            $this->io->text("Input path is not a dir.");
            exit(0);
        }

        if ($tree) {
            $tree = $this->excludeFolders($tree);

            foreach($tree as $value) {
                $currentPathToFile = $path . DIRECTORY_SEPARATOR . $value;
                if (!is_dir($currentPathToFile) && is_file($currentPathToFile))
                    $this->processLine($currentPathToFile);
                else if (is_dir($currentPathToFile))
                    $this->processFiles($currentPathToFile);
            }
        }
    }

    private function copyRecursively($src, $dst) {

        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . "/" . $file))
                    $this->copyRecursively($src . '/' . $file, $dst . '/' . $file);
                else
                    copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
        closedir($dir);
    }

    private function reapsRest() {
        $folders = ["/_sys/images", "/_sys/fonts", "/_sys/ckeditor/skins/moono", "/_sys/classes/xsd"];
        foreach($folders as $folder) {
            $this->copyRecursively($this->inputPath . $folder, $this->outputPath . $folder);
        }
    }

    private function removeUselessFilesAndFolders() { # must be one of last act...
        $path = $this->outputPath;
        $filesToExclude = [
            ".syscor",
            "vivo",
            "nbproject",
            "_sys/classes/util/ArrayIsoToUtf8.php"
        ];

        $this->io->text("Removing deprecated things...");

        foreach ($filesToExclude as $item) {
            $path .= $item;
            if (is_dir($path))
                `rm -rf {$path}`;
            else if (is_file($path))
                unlink($path);
        }
    }

    private function removeAnnoyingChars($path) { // WIP

        $foldersToSweep = ["/ckeditor"];
        $tree = scandir($this->outputPath . "/_sys" . $path);

        foreach ($tree as $value) {
            if (($value != '.') && ($value != '..')) {
                $currentPath = $this->outputPath . "/_sys" . $path . DIRECTORY_SEPARATOR . $value;
                if (!is_dir($currentPath) && is_file($currentPath)) {
                    // if ($file = fopen($currentPath, 'r+')) {
                    //     while (!feof($file)) {
                    //         $line = fgets($file);
                    //         if (preg_match("/(ï|»|¿)/", $line)) {
                    //             $line = preg_replace("/(ï|»|¿)/", "", $line);
                    //         }
                    //         fwrite($file, $line);
                    //     }
                    // }
                    // fclose($file);

                    $file = file($currentPath);
                    $lines = [];
                    foreach($file as $line) {
                        if (preg_match("/(ï|»|¿)/", $line)) {
                            $line = preg_replace("/(ï|»|¿)/", "", $line);
                        }
                        $lines = $line . "\n";
                    }
                    // $new = implode();
                    file_put_contents($currentPath, $lines); // FIXME: nao ta escrevendo direito no arquivo

                } else if (is_dir($currentPath)) {
                    $this->removeAnnoyingChars($path . DIRECTORY_SEPARATOR . $value);
                }
            }
        }
    }

    private function polishingConvertedProject() { # maybe will be deprecated
        $this->removeAnnoyingChars("/ckeditor");
    }

    public function executeConversionProcedures($args) { # realiza cerimonia
        $this->io->section("Execute Conversion Procedures");

        // $this->loadProject();
        $this->arrangeOutput();
        $this->cloneDirectoryTree($this->inputPath);
        $this->processFiles($this->inputPath);
        $this->reapsRest();
        $this->removeUselessFilesAndFolders(); # must be one of last act...
        // $this->polishingConvertedProject();
    }
}
