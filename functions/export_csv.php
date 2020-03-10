<?php

class CSV{

  static function export($data,$filename){
    header('Content-Type: text/csv;');
    header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
    $i = 0; //variable d'iteration pour permettre de savoir lorsqu'on est sur la premiere ligne
    foreach ($data as $v) {
      //si je suis sur la première ligne je peux afficher les valeurs du tableau
      if ($i==0) {
        echo "\n".'"'.utf8_decode(implode('";"',array_keys($v))).'"';//permet de récupérer l'ensemble des indexs sélectionner dans la query
      }
      echo "\n".'"'.utf8_decode(implode('";"',$v)).'"';//récupère les valeurs
      $i++;
    }
  }
}
?>
