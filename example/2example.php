<?php 

require '../src/System3D/IFC2JSON/IFC2JSON.php'; 
use System3D\IFC2JSON\IFC2JSON;

$IFC2JSON 	= new IFC2JSON( "model.ifc" );

print_r($IFC2JSON);