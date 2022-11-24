<?php

namespace Foundry;

use Sequel\Sequel;

class FoundryTablier{

    /**
     * Build up the database
     * and create all the tables.
     */

    public function build(){

        /**
         * The CLI helper to build
         * the database based on the
         * input from Init/tables and
         * system's Tablier/tables.
         */

        echo "\e[32mTablier creating tables...\n";

        echo "\n\e[33mSystem:\n\e[39m";

        foreach ( glob(ROOTPATH."/vendor/vesperphp/sequel/tables/*.php") as $filename) { 
            include $filename; 
        }

        /**
         * Run the init/tables
         */

        echo "\n\e[33mApp:\n\e[39m";

        foreach ( glob(ROOTPATH."/sequel/tablier/*.php") as $filename) { 
            include $filename; 
        }

        echo "\n\e[32mDone.";

    }

    /**
     * Drop all tables within
     * the database.
     */

    public function drop(){

        echo "\e[32mTablier dropping tables...\n";

        /** 
         * Run an SQL command
         * to drop all tables
         * within this DB.
         */

        $res = Sequel::sql("SHOW TABLES;");

        $sql = "SET FOREIGN_KEY_CHECKS = 0;
        SET GROUP_CONCAT_MAX_LEN=32768;
        SET @tables = NULL;
        SELECT GROUP_CONCAT('`', table_name, '`') INTO @tables
        FROM information_schema.tables
        WHERE table_schema = (SELECT DATABASE());
        SELECT IFNULL(@tables,'dummy') INTO @tables;
        SET @tables = CONCAT('DROP TABLE IF EXISTS ', @tables);
        PREPARE stmt FROM @tables;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SET FOREIGN_KEY_CHECKS = 1;";

        Sequel::sql($sql);

        foreach($res['all'] as $table){
            echo "\e[39m".$table['Tables_in_test']." \e[31mdeleted!\e[39m \n";
        }

        echo "\e[32mDone.";

    }

    /**
     * Run the filler
     * files.
     */

    public function fill(){

        require_once ROOTPATH."/vendor/vesperphp/elemental/service/functions/slug.php";
        /**
         * The CLI helper to build
         * the database based on the
         * input from Init/tables and
         * system's Tablier/tables.
         */

        echo "\e[32mTablier filling tables...\n";

        /**
         * Run the init/tables
         */

        foreach ( glob(ROOTPATH."/sequel/filler/*.php") as $filename) { 
            include $filename; 
        }

        echo "\n\e[32mDone.";

    }

    public function refresh(){

        $this->drop();
        $this->build();
        $this->fill();

        echo "\n\e[32mTriple done.";

    }

}
