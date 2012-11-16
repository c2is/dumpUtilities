<?php
/**
 * Examples for usage of dumpUtilities class
 *
 * @author  PLANCON Sylvain <s.plancon@c2is.fr>
 */

/*
 * All examples below can be combined
 */

// Include dump class
require_once("dumpUtilities.php");


//Basic usage
dumpUtilities::dump("myTable", "my_table.sql");

//Rename fields
$newNames = array(
    "original_name" => "new_name",
    "id_table" => "id",
    "name" => "name"
);
dumpUtilities::dump("myTable", "my_table.sql", $newNames);

//Change tablename
dumpUtilities::dump("myTable", "my_table.sql", null, "myNewTable");

//Add news fields
$newFields = array(
    "newFieldName" => "valueForNewField",
    "version" => "2.0"
);
dumpUtilities::dump("myTable", "my_table.sql", null, null, $newFields);

//Generate INSERT IGNORE statements
dumpUtilities::dump("myTable", "my_table.sql", null, null, null, true);

//Select rows to dump
$sql = "SELECT * FROM myTable WHERE active=1";
dumpUtilities::dump("myTable", "my_table.sql", null, null, null, false, $sql);

//Select fields to dump
$sql = "SELECT id, name, otherField FROM myTable";
dumpUtilities::dump("myTable", "my_table.sql", null, null, null, false, $sql);

//Combine 2 tables in 1
$sql = "SELECT person.*, address.line1, address.line2, address.line3, address.zipcode, address.city, address.country FROM person LEFT JOIN address ON address.id_person=person.id";
dumpUtilities::dump("person", "my_table.sql", null, null, null, false, $sql);

//Default Value for null fields
// In this example, all person without address will have empty fields for address instead null fields
$sql = "SELECT person.*, address.line1, address.line2, address.line3, address.zipcode, address.city, address.country FROM person LEFT JOIN address ON address.id_person=person.id";
$defaultValues = array(
    "line1" => "",
    "line2" => "",
    "line3" => "",
    "zipcode" => "",
    "city" => "",
    "country" => "",
);
dumpUtilities::dump("person", "my_table.sql", null, null, null, false, $sql, $defaultValues);
