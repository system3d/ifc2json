<?php 
require '../vendor/autoload.php'; 
require '../src/System3D/IFC2JSON/IFC2JSON.php'; 
use System3D\IFC2JSON\IFC2JSON;

// $IFC2JSON = new IFC2JSON( "20160414office_model_CV2_fordesign.ifc", true );
$IFC2JSON = new IFC2JSON( "data/3CHAPAS_0.ifc.ifc", true );
$IFC2JSON->download(); 
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">	
	<title></title>
</head>
<body>
	<?php dump( $IFC2JSON->getJson(null, false) ); ?>
	<script>		
		console.log(<?php echo $IFC2JSON->getJson(); ?>);
	</script>
</body>
</html>