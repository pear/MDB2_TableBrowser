<?php
/**
 * This file contains an example use of MD2_Table_Browser for a single table
 *
 * PHP version 5
 *
 * @category  Database
 * @package   MDB2_TableBrowser
 * @author    Isaac Tewolde, <isaac@ticklespace.com>
 * @copyright 2007-2012 Isaac Tewolde
 * @license   http://www.gnu.org/licenses/lgpl.html LGPL v3.0
 * @version   SVN:<svn_id>
 * @link      http://code.google.com/p/mdb2tablebrowser/source/browse/trunk/MDB2_TableBrowser/example.php
 */

require_once "MDB2.php";

define('DSN', 'mysql://username:pass@localhost/animal_db');


/**
 * The example below relies on the following data from the table tbl_animals
 * ID       NAME        TYPE        LIFESPAN
 * 1        dog         mammal      12
 * 2        cat         mammal      30
 * 3        parrot      bird        60
 * 4        shark       fish        30
 * 5        dolphin     mammal      50
 * 6        crocodile   reptile     50
 * 7        snake       reptile     20
 * 8        spider      arachnid    1
 * 9        housefly    insect      1
 * 10       ostrich     bird        35
 * 11       bat         mammal      6
 * 12       human       mammal      100
 */

$dsn     = 'mysql://username:pass@localhost/animal_db';
$options = array(
    'debug' => 2,
    'result_buffering' => false,
);

$mdb2 = MDB2::singleton($dsn, $options);

//Create the table
setupDb($mdb2);

$mdb2->loadModule('TableBrowser');

//Create a table browser for the tbl_animals table, and specify id as the primary key
$browser = $mdb2->tableBrowserFactory('tbl_animals', 'id');

//The data browsing object is now ready
//First insert the needed data
$data = array(
            array(1,'dog','mammal',12),
            array(2,'cat','mammal',30),
            array(3,'parrot','bird',60),
            array(4,'shark','fish',30),
            array(5,'dolphin','mammal',50),
            array(6,'crocodile','reptile',50),
            array(7,'snake','reptile',20),
            array(8,'spider','arachnid',1),
            array(9,'housefly','insect',1),
            array(10,'ostrich','bird',35),
            array(11,'bat','mammal',6),
            array(12,'human','mammal',100)
            );
$browser->insertRows(array('id', 'name','type','lifespan'), $data);

//Get info on a single animal, getRow returns a hash array and getRows returns 
//an MDB2_Result object 
$browser->getRow(1);
print "\n" . $browser->getLastSQL();
//Prints: SELECT `ID`,`NAME`,`TYPE`,`LIFESPAN` FROM tbl_animals WHERE (`id` = 1)

//Get info on 3 animals in the table sorted by name starting with the 5th animal
$browser->getRows('name', 3, 5);
print "\n" . $browser->getLastSQL();
//The limits/offsets are not shown below as they are set by mdb2 library
//Prints: SELECT `ID`,`NAME`,`TYPE`,`LIFESPAN` FROM tbl_animals

//Hide the ID column and rename the column "TYPE" to "Animal Type"
$browser->selectColumns(array('name','type','lifespan'));
$browser->setColumnAlias('TYPE', 'ANIMAL TYPE');
$browser->getRows('name', 3, 5);
print "\n" . $browser->getLastSQL();
//Prints:SELECT `NAME`,`TYPE` AS `ANIMAL TYPE`,`LIFESPAN` FROM tbl_animals

//Get the different kinds of animal types in the table eg: mammal, reptile,...
$browser->getColumnValues('type');
print "\n" . $browser->getLastSQL();
//Prints:SELECT DISTINCT `TYPE` FROM tbl_animals

//This also works with aliases you have set up
$browser->getColumnValues('ANIMAL TYPE');
print "\n" . $browser->getLastSQL();
//Prints:SELECT DISTINCT `TYPE` FROM tbl_animals

/*
 * Example using filters, look for mammals with a lifespan <60 years Multiple
 * filters can be added and removed. This functionality can be used to quickly
 * build a browsing application that gives the user the freedom to traverse the
 * table data in different ways.
 */
$browser->addFilter('MaxAge', 'lifespan', '<=', 60);
$browser->addFilter('AnimalType', 'type', '=', 'mammal');
//Once a filter has been set, it affects the browser's output
$browser->getRows();
print "\n" . $browser->getLastSQL();
//Prints: SELECT `NAME`,`TYPE` AS `ANIMAL TYPE`,`LIFESPAN` FROM tbl_animals WHERE (`lifespan` <= 60 AND `type` = 'mammal')

//A single filter can be removed by specifying the filter name
$browser->removeFilter('MaxAge');
$browser->getRows();
print "\n" . $browser->getLastSQL();
//Prints: SELECT `NAME`,`TYPE` AS `ANIMAL TYPE`,`LIFESPAN` FROM tbl_animals WHERE (`type` = 'mammal')

//All Filters set can be cleared using this method
$browser->resetFilters();
$browser->getRows();
print "\n" . $browser->getLastSQL();
//Prints: SELECT `NAME`,`TYPE` AS `ANIMAL TYPE`,`LIFESPAN` FROM tbl_animals

//Insert a new row
$rowData = array('id'=>13, 'name' => 'duck','type' => 'bird','lifespan' => 5);
$browser->insertRow($rowData);
print "\n" . $browser->getLastSQL();
//The whole statement is not shown as this sql is prepared and excectues by mdb2 library
//Prints: INSERT INTO tbl_animals VALUES (?,?,?,?)

//Update the parrot's data
$rowData             = $browser->getRow(3);
$rowData['lifespan'] = 65;
$browser->updateRow(3, $rowData);

print "\n" . $browser->getLastSQL();
//Prints: UPDATE tbl_animals SET `id`= NULL,`name`= 'parrot',`type`= NULL,`lifespan`= 65 WHERE (`id` = 3)

$browser->addFilter('AnimalType', 'type', '=', 'bird');
//Clear all colum selections and aliases
$browser->resetSelectColumns();
$browser->resetColumAliases();
$browser->getRows();
print "\n" . $browser->getLastSQL();
//Prints: SELECT `ID`,`NAME`,`TYPE`,`LIFESPAN` FROM tbl_animals WHERE (`type` = 'bird')

/**
 * You can also create multiple filter chains to query for different conditions 
 * in parallel. Say you were interested in mammals with a lifespan > 30 or birds
 * with a lifespan < 10. This can be accomplished using 2 filter chains as
 * follows.
 */
$browser->resetFilters();
//Call the first chain 'Mammal Group' and the second "Bird Group'. You can use any identifier that makes sense to you
$browser->createFilterChain('Mammal Group');
$browser->createFilterChain('Bird Group');

//Define the mammals filter chain
$browser->selectFilterChain('Mammal Group');
$browser->addFilter('AnimalType', 'type', '=', 'mammal', 'Mammal Group');
$browser->addFilter('Lifespan', 'lifespan', '>', 30, 'Mammal Group');

//Define the birds filter chain
$browser->selectFilterChain('Bird Group');
$browser->addFilter('AnimalType', 'type', '=', 'bird', 'Bird Group');
$browser->addFilter('Lifespan', 'lifespan', '<', 10, 'Bird Group');

$browser->getRows();
print "\n" . $browser->getLastSQL();
//Prints: SELECT `ID`,`NAME`,`TYPE`,`LIFESPAN` FROM tbl_animals WHERE ((`type` = 'mammal' AND `lifespan` > 30)) OR ((`type` = 'bird' AND `lifespan` < 10))

//This resets all the filters chains
$browser->resetAllFilters();

//Switch back to the default filter chain
$browser->selectFilterChain();

//You can delete a filterChain like this. Any user defined filter chain can be removed
//But the default filter chain is always there
$browser->deleteFilterChain('Mammal Group');
$browser->deleteFilterChain('Bird Group');

//You can add custom columns as well for columns like 'md5()' or your own custom functions
$browser->addCustomColumn('md5(TYPE)', 'Special Column');
$browser->getRows();
print "\n" . $browser->getLastSQL();
//Prints: SELECT `id`,`name`,`type`,`lifespan`,md5(TYPE) AS `Special Column` FROM tbl_animals

$browser->removeCustomColumn('md5(TYPE)');

//You can also add grouping and sorting methods
$browser->setOrderBy('lifespan');
$browser->setGroupBy('type');
$browser->selectColumns(array('type','lifespan'));
$browser->addCustomColumn('count(*)', 'Number of Species');
$browser->getRows();
print "\n" . $browser->getLastSQL();
//Prints: SELECT `type`,`lifespan`,count(*) AS `Number of Species` FROM tbl_animals GROUP BY `type` ORDER BY `lifespan`

//Delete sharks (id 4)
$browser->deleteRow(4);
print "\n" . $browser->getLastSQL() . "\n\n";
//Prints: DELETE  FROM tbl_animals WHERE (`id` = 4)

/**
 * Creates the tbl_animals table
 *
 * @param ref &$mdb2 An mdb2 object reference
 * 
 * @return void
 */
function setupDb(&$mdb2)
{
    // loading the Manager module
    $mdb2->loadModule('Manager');
    $tableDefinition = array (
        'id' => array (
            'type' => 'integer',
            'unsigned' => 1,
            'notnull' => 1,
            'default' => 0,
        ),
        'name' => array (
            'type' => 'text',
            'length' => 300,
            'notnull' => 1
        ),
        'type' => array (
            'type' => 'text',
            'length' => 300,
            'notnull' => 1
        ),
        'lifespan' => array (
            'type' => 'integer',
            'unsigned' => 1,
            'notnull' => 1,
            'default' => 0,
        ),
    );
    
    $tableConstraints = array (
        'primary' => true,
        'fields' => array (
            'id' => array()
        )
    );
    $mdb2->dropTable('tbl_animals');
    $mdb2->createTable('tbl_animals', $tableDefinition);
    $mdb2->createConstraint('tbl_animals', 'primary_key', $tableConstraints);
    $mdb2->createSequence('primary_key');
    
}


?>