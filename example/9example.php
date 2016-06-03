<?php 

require '../vendor/autoload.php'; 
require '../src/System3D/IFC2JSON/IFC2JSON.php'; 
use System3D\IFC2JSON\IFC2JSON;

// $IFC2JSON = new IFC2JSON( "20160414office_model_CV2_fordesign.ifc", true );
$IFC2JSON = new IFC2JSON( "2chapas.ifc.ifc", true );
$IFC2JSON->download(); 

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">	
	<title></title>
</head>
<body>
	<!-- <pre><code class="html" id="code"></code></pre> -->
	<?php echo $IFC2JSON->getJson(); ?>
	<script>
		// function syntaxHighlight(json) {
		//     if (typeof json != 'string') {
		//         json = JSON.stringify(json, undefined, 2);
		//     }
		//     json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		//     return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
		//         var cls = 'number';
		//         if (/^"/.test(match)) {
		//             if (/:$/.test(match)) {
		//                 cls = 'key';
		//             } else {
		//                 cls = 'string';
		//             }
		//         } else if (/true|false/.test(match)) {
		//             cls = 'boolean';
		//         } else if (/null/.test(match)) {
		//             cls = 'null';
		//         }
		//         return '<span class="' + cls + '">' + match + '</span>';
		//     });
		// }
		// var content = syntaxHighlight( <?php echo $IFC2JSON->getJson(); ?> );
		// document.getElementById('code').innerHTML = content;
		console.log(<?php echo $IFC2JSON->getJson(); ?>);
	</script>

	<style>
		pre {outline: 1px solid #ccc; padding: 5px; margin: 5px; }
		.string { color: green; }
		.number { color: darkorange; }
		.boolean { color: blue; }
		.null { color: magenta; }
		.key { color: red; }

	</style>
	
</body>
</html>