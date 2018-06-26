#!/bin/bash

function dbbackup {
  mysqldump
}


        $script_backup_db = "
            mysqldump --add-drop-table -h {$this->host} -u {$this->username} -p{$this->password} {$this->dbname} \
            | bzip2 > backup_{$this->dbname}.sql.bz2
        ";

        $script_import_db = "
            bunzip2 < backup_{$this->dbname}.sql.bz2 \
            | mysql -h {$this->host} -u {$this->username} -p{$this->password} {$this->dbname}
        ";

        $script_conversao_db = "
            mysqldump --add-drop-table -h {$this->host} -u {$this->username} -p{$this->password} {$this->dbname} \
            | sed 's/CHARSET=latin1/CHARSET=utf8/g' \
            | iconv -f latin1 -t utf8 \
            | mysql -h {$this->host} -u {$this->username} -p{$this->password} {$this->dbname}
        "; # maybe use 'replace' from mysql are better aproach...
