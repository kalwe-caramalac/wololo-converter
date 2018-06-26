<?php

# Contem procedimentos
# para converter o encode um banco de dados

// namespace Core\ConversorDatabase;

class ConversorDatabase
{
    const DATABASE_TIMEOUT  = 60 * 60 * 16;

    private $encode     = "utf8";
    // private $collation  = "utf8_swedish_ci";
    private $collation  = "utf8_general_ci";

    private $pdo;

    private $host;
    private $username;
    private $password;
    private $port;

    private $bsconv;
    private $iconv;
    private $backup;
    private $backupPath;
    private $backupFileName;

    private $userHome;
    private $new_dbname;

    private $databases;
    private $dbname;
    private $tables;
    private $fields;

    private $io;
    private $args;

    public function __construct($args) {
        $this->args = (object)$args;

        $host = ($this->args->host == "localhost" ) ? '127.0.0.1' : $this->args->host;
        $this->host         = $host;
        $this->username     = $this->args->user_name;
        $this->password     = $this->args->password;
        $this->port         = $this->args->port;
        $this->dbname       = $this->args->dbname;

        $this->io = $this->args->io;
        $this->buildPDO($this->args);

        $this->userHome = trim(`echo \$HOME`);
    }

    private function buildPDO($args) {
        $dsn = "mysql:host={$this->host};" .
               "port={$this->port};" .
               "dbname={$this->dbname}";

        $this->pdo = new PDO($dsn, $args->user_name, $args->password, [
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_OBJ,
                PDO::ATTR_TIMEOUT               => self::DATABASE_TIMEOUT
            ]);
    }

    private function listDatabases() {

        $this->databases = [];
        $dbExclude = ["information_schema", "mysql", "performance_schema"];

        $sql = "
            SELECT SCHEMA_NAME 'database', default_character_set_name 'charset',
            DEFAULT_COLLATION_NAME 'collation' FROM information_schema.SCHEMATA;
        ";

        $databases = $this->pdo->query($sql)->fetchAll(PDO::FETCH_OBJ);

        foreach ($databases as $key => $database) {
            $dbinfo['name']         = $database->database;
            $dbinfo['charset']      = $database->charset;
            $dbinfo['collation']    = $database->collation;
            $this->databases[]      = $dbinfo;
        }
    }

    private function defineActions() {

        $dbOpts = [];
        foreach ($this->databases as $key => $database) {
            $options[] = "Name: " . $database['name'] . "\n      Charset: " . $database['charset'] . "\n";
        }

        $dbchoice = $this->io->choice("Choose a databases to convert...", $options);
        $infos = explode(' ', $dbchoice);
        $this->dbname = trim($infos[1]);

        $charsetOpts = ["utf8", "latin1"];
        $this->encode = $this->io->choice("What 'charset' you want?", $charsetOpts, "utf8");

        $boolOpt = ["No", "Yes"];

        $this->bsconv = strtolower($this->io->choice("Use bit-wise convertion?", $boolOpt, "No"));

        if (!$this->bsconv)
            $this->iconv = strtolower($this->io->choice("Use 'iconv' function conversion? (iconv)", $boolOpt, "No"));

        $this->backup = strtolower($this->io->choice("Should make a back-up? [save in '{$this->userHome}']", $boolOpt, "No"));

        if ($this->backup == "yes")
            $this->backupPath = $this->userHome;
    }

    public function prepareConversion() {
        $this->io->section("Prepering to convert database...");
        $this->listDatabases();
        $this->defineActions();
    }

    private function doBackup() {
        $this->backupFileName = "backup_{$this->dbname}_$(date +%Y%m%d).sql.bz2";
        if (chdir($this->backupPath)) {
            $script = "mysqldump --add-drop-table -h {$this->host} -u {$this->username} --port {$this->port}  -p{$this->password} {$this->dbname} \
            2>/dev/null | bzip2 --force --quiet > {$this->backupFileName}"; /* '--force' is used to do a back-up anyway */

            exec($script, $output, $ret_val);
            if ($ret_val != 0) {
                $this->io->warning("It seems like we had a problem with back-up. Please check backup file to avoid any trouble ahead.");
            } else {
                $this->io->success("Back-up created with success!!! ");
                $this->io->text($output);
            }
        }
    }

    private function setDatabaseDefaultCharset() {
        $sql = "
            ALTER DATABASE `{$this->dbname}`
            DEFAULT CHARACTER SET {$this->encode}
        ";
        $this->pdo->exec($sql);
    }

    private function discoveryTablesInDatabase() {

        $this->tables = [];

        $sql = "
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = '{$this->dbname}';
        ";

        $this->tables = $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    private function setTableCollate($table_name) {
        $sql = "
            UPDATE information_schema.TABLES
            SET TABLE_COLLATION = '{$this->collation}'
            WHERE TABLE_SCHEMA = '{$this->dbname}' AND TABLE_NAME = '{$table_name}'
        ";
        $this->pdo->exec($sql);
    }

    private function sweepColumnsInTable($table_name) {

        $sql = "
            SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE
            FROM information_schema.columns
            WHERE table_schema = '{$this->dbname}' AND table_name = '{$table_name}'
        ";

        $columns = $this->pdo->query($sql)->fetchAll(PDO::FETCH_OBJ);

        return $columns;
    }

    private function changeColumnEncodeTypeInTable($table_name) {

        $columns = $this->sweepColumnsInTable($table_name);

        $columnTypesToExclude = "/(int)|(decimal)|(tinyint)|(smallint)|(mediumint)|(bigint)|(float)|(double)|(date)|(datetime)|(timestamp)|(time)|(year)/i";

        foreach ($columns as $key => $column) {

            if (!preg_match($columnTypesToExclude, $column->DATA_TYPE)) {
                $sql_column = "
                    ALTER TABLE {$this->dbname}.{$table_name}
                    MODIFY COLUMN {$column->COLUMN_NAME} {$column->COLUMN_TYPE}
                    CHARACTER SET {$this->encode}
                ";
                $this->pdo->exec($sql_column);
            }
        }
    }

    private function executeScripts() {

        $this->discoveryTablesInDatabase();

        foreach ($this->tables as $table) {
            // $this->io->text($table);
            // $this->setTableCollate($table);
            // $this->changeColumnEncodeTypeInTable($table);
        }
        // $this->changeColumnEncodeTypeInTable('uf'); # debug only
    }

    private function createDatabase() {

        $this->new_dbname = $this->dbname . "_utf8";

        $sql = "
            CREATE DATABASE IF NOT EXISTS {$this->new_dbname}
            CHARACTER SET {$this->encode} COLLATE {$this->collation}
        ";
        $this->pdo->exec($sql);
    }

    private function importDatabase($dbname) { # if convertion fail, restore database
        # TODO: this script need a validation
        $script = "
            bunzip < {$dbname} \
            mysql -h {$this->host} -u {$this->username} --port {$this->port} \
            -p{$this->password} {$newdbname} 2>/dev/null
        ";
        $this->pdo->exec($script);
    }

    private function restoreDatabase() {
        # TODO: validar path do nome do arquivo
        $backupPath = $this->backupPath . $this->backupFileName;
        if (is_file($backupPath))
            $this->importDatabase($backupPath);
    }

    private function iconv() {

        $script_conversao_db = "
            mysqldump --add-drop-table -h {$this->host} --port {$this->port} \
            -u {$this->username} -p{$this->password} \
            {$this->dbname} \
            | sed 's/CHARSET=latin1/CHARSET=utf8/g' \
            | iconv -f latin1 -t utf8 \
            | mysql -h {$this->host} --port {$this->port} -u {$this->username} -p{$this->password} \
            {$this->new_dbname}
        ";

        exec($script_conversao_db, $output, $retval);

        if ($retval != 0) {
            $this->io->success("IConvertion (Interactive-Conversoin) finish with success!!!");
        } else {
            $this->io->text($retval);
            $this->io->warning("Canno't make full convertion!");
            $this->io->warning("We gonna restore a back-up.");
            // $this->restoreDatabase();
        }
    }

    private function iconvertion() {
        $this->createDatabase();
        $this->iconv();
    }

    private function bsconvertion() {

        // $this->discoveryTablesInDatabase();

        // foreach($this->tables as $tablename) {
        //     $sql = "
        //         SELECT * FROM {$tablename}
        //     ";
        //     // $res = $this->pdo->query($sql);
        //     $this->io->text("\tTable '{$tablename}'");
        //     foreach($this->pdo->query($sql) as $row) {
        //         $this->io->text(print_r($row, 1));
        //     }
        // }

        // DO ONE TABLE
        $tablename = "acessorio";
        $sql_type = "
                SELECT COLUMN_NAME, DATA_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = '{$this->dbname}' AND TABLE_NAME = '{$tablename}'
        ";

        $columnsType = $this->pdo->query($sql_type)->fetchAll(PDO::FETCH_OBJ);
        $columnsToConvert = [];

        // echo print_r($columnsType, 1);
        foreach($columnsType as $key => $type) {
            $ptt = "/(char|varchar|text|blob|tinyblob|tinytext|mediumblob|mediumtext|longblob|longtext)/i"; // enum
            if (preg_match($ptt, $type->DATA_TYPE))
                $columnsToConvert[] = $type->COLUMN_NAME;
        }

        $sql_q = "
            SELECT * FROM {$tablename}
        ";

        $seqchar = "/(á|ã|â|é|ẽ|ê|í|ĩ|î|ó|õ|ô|ú|ũ|û|ç)/i";

        foreach($this->pdo->query($sql_q) as $row) {

            // echo print_r($row, 1);

            foreach ($columnsToConvert as $key => $columnName) {
                if (preg_match_all($seqchar, $row->{$columnName})) {

                    echo "from preg_match\n";
                    echo "$columnName ";
                    echo print_r($row->{$columnName}, 1) . "\n";

                    $data = preg_replace("ptt", "plc", $row->{$columnName});

                    $sql_i = "
                        UPDATE $tablename
                        SET $row->{$columnName}
                        WHERE ac_id = {$row->ac_id}
                    ";

                    $this->pdo->exec($sql_i);
                }
                // echo "$columnName ";
                // echo print_r($row->{$columnName}, 1) . "\n";
            }

            echo "\n"; # debug only
        }

        // echo print_r($columnsToConvert, 1);
        // DO ONE TABLE END


        // $sql = "SELECT * FROM {$tablename}";
        // foreach($this->pdo->query($sql) as $row) {

        //     // $this->io->text(print_r($row, 1));

        //     // foreach($row as $data) {
        //     //     $data

        //     // }
        // }
    }

    public function executeConversionProcedures($args) {

        if ($this->backup == "yes")
            $this->doBackup();

        $this->io->section("Database Wololo!!!");

        if($this->bsconv == "yes") {
            $this->bsconvertion();
        } elseif ($this->iconv == "yes") {
            $this->iconvertion();
        } else {
            $this->setDatabaseDefaultCharset();
            $this->executeScripts();
        }
    }
}
