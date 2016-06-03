<?php 

require '../vendor/autoload.php'; 
require '../src/System3D/IFC2JSON/IFC2JSON.php'; 
use System3D\IFC2JSON\IFC2JSON;

$IFC2JSON = new IFC2JSON( "TEMPLATE_0.ifc", false );

echo "<pre>";
echo $IFC2JSON->getJson();