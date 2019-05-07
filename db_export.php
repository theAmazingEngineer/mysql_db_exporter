<?php
$host = 'localhost';	// host address
$user = 'root';		// user name
$pass = '******';	// user password
$name = 'my_database';	// data base name
$output = 'download';	// file | download
$tables = false;	// download specific tables. Ex: array('my_table_1', 'my_table_2', 'my_table_3')

$mysqli = new mysqli($host, $user, $pass, $name);
$mysqli->select_db($name);
$mysqli->query("SET NAMES 'utf8'");

$queryTables = $mysqli->query('SHOW TABLES');

while ($row = $queryTables->fetch_row())
{
	$target_tables[] = $row[0];
}

if ($tables !== false)
{
	$target_tables = array_intersect( $target_tables, $tables);
}

foreach ($target_tables as $table)
{
	$result		= $mysqli->query('SELECT * FROM '.$table);
	$fields_amount	= $result->field_count;
	$rows_num	= $mysqli->affected_rows;
	
	$res		= $mysqli->query('SHOW CREATE TABLE '.$table);
	$TableMLine	= $res->fetch_row();
	$content	= (!isset($content) ?  '' : $content) . "\n\n" . $TableMLine[1] . ";\n\n";
	
	for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter = 0)
	{
		while ($row = $result->fetch_row())
		{
			// start a new cycle every 100 commands
			if ($st_counter%100 == 0 || $st_counter == 0)
			{
				$content .= "\nINSERT INTO ".$table." VALUES";
			}
			$content .= "\n(";
			
			for ($j = 0; $j < $fields_amount; $j++)
			{
				$row[$j] = str_replace('"',"'", $row[$j]);
				$row[$j] = str_replace("\n","\\n", addslashes($row[$j]) );
				
				if (isset($row[$j]))
				{
					$content .= '"'.$row[$j].'"' ;
				}
				else
				{
					$content .= '""';
				}
				
				if ($j < ($fields_amount - 1))
				{
					$content .= ',';
				}
			}
			$content .=")";
			
			// ends a cycle every 100 commands
			if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num)
			{
				$content .= ";";
			}
			else
			{
				$content .= ",";
			}
			$st_counter = $st_counter + 1;
		}
	}
	$content .="\n\n\n";
}

$backup_name = date("Y_m_d_H_m_s") . "_" . $name.".sql";

if ($output == 'download')
{
	// force to download file
	header("Content-Type: application/octet-stream");
	header("Content-Transfer-Encoding: Binary");
	header("Content-disposition: attachment; filename=\"" . $backup_name . "\"");
	echo $content;
	exit;
}
else
{
	// stores the sql file at web server
	$fp = fopen($backup_name, "wb");
	fwrite($fp, $content);
	fclose($fp);
	exit;
}