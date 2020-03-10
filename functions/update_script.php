<?php
include "db_connect.php";
$db = PDOFactory::getConnection();
try{
	set_time_limit(0);

} catch(PDOException $e){
	echo $e->getMessage();
}
?>
