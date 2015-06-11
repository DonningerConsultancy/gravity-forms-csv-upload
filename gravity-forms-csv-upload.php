<?php
/*
Plugin Name: Gravity Forms Entries CSV Upload
Plugin URI:  http://donninger.nl
Description: Upload CSV entries in a configured form
Version:     0.1-alpha
Author:      Donninger Consultancy
Author URI:  http://donninger.nl
Text Domain: gravity-forms
Domain Path: /lang
 */

 //------------------------------------------
 if (class_exists("GFForms")) {
     GFForms::include_addon_framework();

     class GFCsvUploadAddon extends GFAddOn {

         protected $_version = "1.1";
         protected $_min_gravityforms_version = "1.7.9999";
         protected $_slug = "gravity-forms-csv-upload";
         protected $_path = "gravity-forms-csv-upload/gravity-forms-csv-upload.php";
         protected $_full_path = __FILE__;
         protected $_title = "Gravity Forms CSV Entries Upload";
         protected $_short_title = "CSV Upload";

         //CSV options
         private $delimiter;
         private $newline;

         private $filterKey;
         private $formId;

         public function init(){
             parent::init();

             require_once("config.inc.php");
             $this->delimiter = $config["delimiter"];
             $this->newline = $config["newline"];
             $this->filterKey = $config["filterKey"];
             $this->formId = $config["formId"];
         }

         public function plugin_page() {
           if(isset($_POST["submit"])) {
             $this->handleUpload();
           } else {
             $returnValue = "<form method=\"post\" enctype=\"multipart/form-data\">\n";
             $returnValue .= "<input type=\"file\" name=\"csv\"/>\n";
             $returnValue .= "<input type=\"submit\" name=\"submit\" value=\"upload\"/>\n";
             $returnValue .= "</form>\n";
             echo $returnValue;
           }
         }

         private function handleUpload() {
           //echo "Upload!!";
           //echo "<pre>";
           //var_dump($_FILES);
           //echo "</pre>";
           $errors = false;
           $recordsImported = 0;
           if($_FILES["csv"]["type"] == "text/csv") {
             //echo "CSV!";
             $row = 1;
             if (($handle = fopen($_FILES["csv"]["tmp_name"], "r")) !== FALSE) {
               while (($data = fgetcsv($handle, 1000, $this->delimiter)) !== FALSE) {
                 if($row == 1) { $fieldKeys = $data; }
                 elseif($row == 2) {  } //do nothing
                 else {
                   if($this->addEntry($fieldKeys, $data)) {
                     echo "Regel $row geimporteerd!<br/>\n";
                     $recordsImported++;
                   } else {
                     $errors .= "Fout bij het importeren van entry op regel $row:<br/>\n";
                     //echo "<hr/>"; var_dump($data); echo "<hr/>";
                   }
                 }
                 $row++;
                 echo "<hr/>";
               }
               //var_dump($recordIds);
               fclose($handle);
               echo "$recordsImported records geimporteerd!<br/>\n";
             } else {
               $errors .= "Upload mislukt! Kon bestand " . $_FILES["csv"]["tmp_name"] . " niet lezen. Upload is <strong>niet</strong> uitgevoerd!<br/>\n";
             }
           } else {
             $errors .= "Verkeerd bestandstype! Gevonden: '" . $_FILES["csv"]["type"] . "', verwacht: 'text/csv'. Upload is <strong>niet</strong> uitgevoerd! <br/>\n";
           }
           if($errors !== false) {
             echo "<h2 style=\"color: red\">Fout gevonden!</h2>\n<p>";
             echo $errors;
             echo "</p>\n";
           }
         }

         private function addEntry($fieldKeys, $data) {
           $user = wp_get_current_user();

           //set up the basic entry fields
           $entry = array();
           $entry["form_id"] = $this->formId;
           $entry["created_by"] = $user->ID;
           $entry["status"] = "active";
           $entry["date_created"] = date("Y-m-d H:i:s");
           $iterator = 0;

           //check if nr of fields matches with first row (ids)
           if( count($fieldKeys) != count($data)) {
             echo "Het aantal velden in het sjabloon komt niet overeen met het te importeren record (aantal keys: " . count($fieldKeys) . ", aantal waarden: " . count($data) . ")<br/>\n";
             echo "Inhoud regel: "; var_dump($data); echo "<br/>";
             return false;
           }

           //iterate fields
           $valueFound = false;
           foreach($fieldKeys as $key) {
             $entry[$key] = $data[$iterator];
             if($data[$iterator]!="") {
               $valueFound = true;
             }
             $iterator++;
           }
           if(!$valueFound) { echo "Lege regel gevonden.<br/>"; return false;}

           //return id of entry or false in case of error
           return GFAPI::add_entry( $entry );
         }
     }

     new GFCsvUploadAddon();
 }
