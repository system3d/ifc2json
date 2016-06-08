<?php 

require '../vendor/autoload.php'; 
require '../src/System3D/IFC2JSON/IFC2JSON.php'; 
use System3D\IFC2JSON\IFC2JSON;

// $IFC2JSON = new IFC2JSON( "20160414office_model_CV2_fordesign.ifc", true );
$IFC2JSON = new IFC2JSON( "data/3D_BASE_TESTE_0.ifc", true );
$IFC2JSON->download(); 

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">	
	<title></title>
</head>
<body>
	<?php echo $IFC2JSON->getJson(); ?>
	<script>		
		console.log(<?php echo $IFC2JSON->getJson(); ?>);
	</script>
</body>
</html>