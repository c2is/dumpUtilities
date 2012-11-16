<?php
/**
 * Class dumpUtilies v1.0
 *
 * Utilities functions for dumping table.
 *
 * Possible specials operations :
 *  - Rename fields and select specific fields
 *  - Rename table
 *  - Add new fields with specific value for each
 *  - Add default value for fields with NULL value
 *  - Generate INSERT IGNORE
 *
 * @author  PLANCON Sylvain <s.plancon@c2is.fr>
 */

// Config file with database information
require_once('config.inc.php');

class dumpUtilities
{
    const host = ORIGIN_HOST;
    const dbname = ORIGIN_DB_NAME;
    const dbuser = ORIGIN_DB_USER;
    const dbpasswd = ORIGIN_DB_PASSWORD;

    /**
     * @static Create dump file for a table with specifics parameters
     * @param $pTable   string  Name of the table to dump
     * @param $pFile    string  Name of the dump file, name can be contain the path to the file
     * @param $pCorrespondences  array Correspondences between original fields and new fields. Must be an array with original fields as keys and new fields as values, If ommited all original fields are kept.
     * @param $pNewTable    string Name of the new table. If ommited the same name is kept.
     * @param $pNewDefaultsFields array Array with new fields to insert.
     * @param $withIgnore boolean Generate 'INSERT IGNORE' statement instead 'INSERT' statement
     * @param $specificSQL mixed SQL statement to select or create specific row to dump. If ommited select all rows from $pTable.
     * @param $pDefaultValues array Insert default value instead null value for specified fields
     *
     * !!! WARNING, if $pCorrespondences is given, only fields in this array were dumping.
     */
    public static function dump ($pTable, $pFile, $pCorrespondences = array(), $pNewTable = "", $pNewDefaultsFields = array(), $withIgnore = false, $specificSQL = null, $pDefaultValues = array()) {

        if (!isset($pTable) || $pTable == "") {
            echo("Le nom de la table à exporter n'est pas spécifié");
            return false;
        }

        if (!isset($pFile) || $pFile == "") {
            echo("Le nom du fichier dump est vide");
            return false;
        }

        if (!is_array($pCorrespondences)) {
            echo("Le tableau de correspondance attendu n'est pas un tableau");
            return false;
        }

        $dumpfile = fopen($pFile, 'w');
        if (!$dumpfile) {
            echo("Impossible de créer le fichier dump(".$pFile.")");
            return false;
        } else {

            if (!mysql_connect(self::host, self::dbuser, self::dbpasswd)) {
                echo("Connection au serveur impossible");
                return false;
            }

            if (!mysql_select_db(self::dbname)) {
                echo("Connection à la base de données impossible");
                return false;
            }

            fwrite($dumpfile, "-- " . self::dbname . " Dump".PHP_EOL);

            if (isset($pNewTable) && $pNewTable != "" && $pTable != $pNewTable) {
                fwrite($dumpfile, "-- Table ".$pTable." vers ".$pNewTable.PHP_EOL);
                $destTable = $pNewTable;
            } else {
                fwrite($dumpfile, "-- Table ".$pTable.PHP_EOL);
                $destTable = $pTable;
            }

            // Récupération de l'ancienne TABLE si pas de jeu résultat spécifique
            if ($specificSQL != null) {
                $rstSELECT = mysql_query($specificSQL);
            } else {
                $strSELECT = "SELECT * FROM `".$pTable."`";
                $rstSELECT = mysql_query($strSELECT);
            }
            $countExport = 0;
            $namesFields = "";
            $valuesFields = "";
            $withCorrespondence = false;

            while ($row = mysql_fetch_assoc($rstSELECT)) {
                if ($countExport == 0) {
                    if (count($pCorrespondences) > 0) {
                        $namesFields = implode(', ', $pCorrespondences);
                        $withCorrespondence = true;
                    } else {
                        $namesFields = implode(', ', array_keys($row));
                    }
                    if (count($pNewDefaultsFields) > 0) {
                        $namesFields .= ', '.implode(', ', array_keys($pNewDefaultsFields));
                    }
                }

                if ($withCorrespondence) {
                    $values = array();
                    foreach ($pCorrespondences as $k => $v) {
                        if ($row[$k] == null) {
                            if (isset($pDefaultValues[$v])) {
                                $values[$v] = addslashes($pDefaultValues[$v]);
                            } else {
                                $values[$v] = 'NULL';
                            }
                        } else {
                            $values[$v] = addslashes(utf8_encode($row[$k]));
                        }
                        $valuesFields = implode('\', \'', $values);
                    }
                } else {
                    foreach ($row as $k => $v) {
                        if ($row[$k] == null) {
                            if (isset($pDefaultValues[$v])) {
                                $values[$v] = addslashes($pDefaultValues[$v]);
                            } else {
                                $values[$v] = 'NULL';
                            }
                        } else {
                            $values[$k] = addslashes(utf8_encode($row[$k]));
                        }
                        $valuesFields = implode('\', \'', $values);
                    }
                }

                if (count($pNewDefaultsFields) > 0) {
                    $valuesFields .= '\', \''.implode('\', \'', $pNewDefaultsFields);
                }

                $valuesFields = '\''.$valuesFields.'\'';
                // On retire les simples quotes autour de certains mots clés ou fonction MySQL
                $valuesFields = preg_replace("`'NOW\(\)'`", "NOW()", $valuesFields);
                $valuesFields = preg_replace("`'NULL'`", "NULL", $valuesFields);

                if ($withIgnore) {
                    fwrite($dumpfile, 'INSERT IGNORE INTO `'.$destTable.'` ('.$namesFields.') VALUES ('.$valuesFields.');'.PHP_EOL);
                } else {
                    fwrite($dumpfile, 'INSERT INTO `'.$destTable.'` ('.$namesFields.') VALUES ('.$valuesFields.');'.PHP_EOL);
                }
                $countExport++;
            }
            fclose($dumpfile);
            echo "Dump réalisé avec succès dans le fichier '" . $pFile . "'";
            return true;
        }
    }
}
