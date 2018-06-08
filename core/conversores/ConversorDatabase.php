<?php

# Contem procedimentos
# para converter o encode um banco de dados para unicode
#
# varre todos os bancos listados no config.inc
# vai executando em cada ambiente

// namespace Core\ConversorDatabase;
// include __DIR__ . "/Conversor.php";

class ConversorDatabase
{
    const DEFOUT_ENCODE     = "UTF-8";
    const DEFIN_ENCODE      = "ISO-8859-1";

    const DATABASE_TIMEOUT  = 60 * 60 * 16;

    private $encode     = "utf8";
    private $encode_ext = "utf8_general_ci"; # latin1_swedish_ci

    private $pdo;

    private $database;
    private $dbname = 'syscor'; # remove or filling from config.inc
    private $tables;
    private $fields;

    private $io;
    private $args;

    public function __construct($args) {
        $this->args = (object)$args;

        $this->io = $this->args->io;
        $this->buildPDO($this->args);
    }

    private function buildPDO($args) {
        $dsn = "mysql:host={$args->host};" .
               "port={$args->port};" .
               "dbname={$args->dbname}";

        // $dsn = "mysql:host=127.0.0.1;" .
        //        "port=11001;" .
        //        "dbname=syscor";

        $this->pdo = new PDO($dsn, $args->user_name, $args->password, [
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_OBJ,
                PDO::ATTR_TIMEOUT               => self::DATABASE_TIMEOUT
            ]);

        // $this->pdo = new PDO($dsn, 'root', 'root', [
        //             PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
        //             PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_OBJ,
        //             PDO::ATTR_TIMEOUT               => self::DATABASE_TIMEOUT
        //         ]);
    }

    private function listDatabases() {

        $sql = "
            SELECT SCHEMA_NAME 'database', default_character_set_name 'charset',
            DEFAULT_COLLATION_NAME 'collation' FROM information_schema.SCHEMATA;
        ";

        $databases = $this->pdo->query($sql)->fetchAll(PDO::FETCH_OBJ);

        // echo print_r($databases, 1);
        foreach ($databases as $key => $database) {
            // print_r($database, 1);
            var_dump($database, 1);
        }
    }

    public function preparaConversao() {
        $this->io->section("Prepering to convert database...");
        // $this->sweepTablesInDatabase();
        // $this->sweepFieldsInTable("uf");
        $this->listDatabases();
    }

    public function executeConversionProcedures($args) {
        $this->io->section("Database Wololo!!!");
        $this->convertDatabase();
    }

    private function convertDatabase() {
        $this->executeScripts();
    }



    private function setDatabaseDefaultCharSetEncode($dbname) {

        $sql = "
            ALTER DATABASE `{$dbname}`
            DEFAULT CHARACTER SET {$this->DEFOUT_ENCODE}
            DEFAULT COLLATE utf8_general_ci
        ";

        $this->pdo->exec($sql);
    }

    private function setColumnCharSetEncode() {

        $sql = "
            ALTER TABLE {$dbname}.{$table_name}
            MODIFY COLUMN {$column_field}
            CHARACTER SET {$encode}
            COLLATE {$encode_ext};
        ";

        $this->pdo->exec($sql);
    }

    private function sweepDatabases() {
        // TODO: find file 'config.inc' and extract all urls for conection
        // username and password, and databases names...

        $sql = "
        ";
    }

    private function sweepTablesInDatabase($dbname = 'syscor') { # todo: alterar syscor

        $this->tables = [];

        $sql = "
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = '{$dbname}';
        ";

        $this->tables = $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN, 0);

        // foreach ($this->tables as $key => $table) {
        //     $this->io->text("Table name => " . $table);
        // }

    }

    private function sweepFieldsInTable($table) {

        $columns = [];

        $sql = "
            SELECT COLUMN_NAME
            FROM information_schema.columns
            WHERE table_schema = '{$this->dbname}' AND table_name = '{$table}'
        ";

        $columns = $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN, 0);

        // echo print_r($columns, 1);
        // foreach ($columns as $key => $column) {
        //     $this->io->text("Column name => " . $column);
        // }
    }

    private function executeScripts() { # maybe this is deprecated cause i need exec scripts all the time...
        // sweepDatabases(); //this should not stay here
        // $this->sweepTablesInDatabase();
        // $this->setDatabaseDefaultCharSetEncode(); // change encode type database
        // $this->changeEncodeTypeTables();
    }

    private function iconvert() {
        // mysqldump --add-drop-table databasename | replace CHARSET=latin1 \
        // CHARSET=utf8 | iconv -f latin1 -t utf8 | mysql database_to_correct

        // varreBancos(); # le todos os configs atraz de urls e dados sobre conn bd
        // armazenaTabelas(); # usa descobreTabelas() que varre as tables no db
        // selecionaSELECTTUDOFROMTabelaNome();
        // converteRow();
        // gravaNoDB();
    }

}
