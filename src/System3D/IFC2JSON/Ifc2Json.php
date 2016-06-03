<?php

namespace System3D\IFC2JSON;

/**
 * Converte as coisa
 */
class IFC2JSON
{

	var $file,
		$data,
		$formated;

	function __construct($file = null, $formated = false)
	{		
		if (!is_file($file) ) {
			return "Informe o arquivo";
		}

		$this->file 	= $file;
		$this->formated = $formated;
		$this->data;		
	}


    public function getJson( $file = null )
    {
    	if (is_file($file) ) {
    		$data = $this->readIFC( $file );
    	}else{
    		$data = $this->readIFC( $this->file );    		
    	}
    	
    	if( $this->formated and $data ){
    		$formated 		= $this->formated( $data, 8 );	
    		// unset( $data['DEMO'] ); // REMOVE DADOS COMPLETOS
    		$data['GEOCONTEXT'] = $formated['GEOREPCONTEXT'];
    		$data['OBJECTS'] = $formated['OBJECTS'];
    		$data['MODELS'] = $formated['MODELS'];
    	}
    	    	    	
    	// dump( $data );
    	// die;

    	return json_encode( $data );
    }


    public function download( $file = null )
    {

    	if ( is_file($file) ) {
    		return $this->readIFC( $file );
    		// return 'Convertendo e cuspindo json do arquivo '.$file.'...';
    	}
    	
    	if (!$this->file) {
    		return 'Informe o arquivo';
    	}
    	
    	$data = $this->readIFC( $this->file );	
    	
    	if( $this->formated ){
    		$formated 	= $this->formated( $data, 8 );	
    		
    		$data['GEOCONTEXT'] = $formated['GEOREPCONTEXT'];
    		$data['OBJECTS'] = $formated['OBJECTS'];
    		$data['MODELS'] = $formated['MODELS'];
    	}
       

    	$data = json_encode( $data );


    	// rename file
    	$filename = str_replace('.ifc.ifc', '.ifc', $this->file);
    	$filename = str_replace('.ifc', '.json', $filename);
    	$filename = basename($filename);    	

    	header('Content-disposition: attachment; filename='.$filename);
		header('Content-type: application/json');
		echo $data;
		die;
    }


    public function readIFC( $file, $filter = null )
    {        	
    	if( !is_file( $file ) )
    		return "Arquivo IFC não encontrado";   	

    	$file = fopen( $file, "r");
		if ($file) {

			$readingsection = '';

			$ifc = [];
			$brokenline = null;

		    while (($line = fgets($file)) !== false) { 


		    	// FIM SEÇÃO ?
		        if( 'ENDSEC;' == substr( $line, 0, 7) ) {
		        	// Fim seção    	
		        	$readingsection = NULL;        	
		        }


		        /* ------------------------------------ */

		        // SEÇÃO HEADER
		        if( 'HEADER' == $readingsection ){

		        	$ifc['HEADER'][] = $line;

		        }; 
		        		       
		        // SEÇÃO DATA
		        if( 'DATA' == $readingsection ){
			    	
			    	// 2nd METHOD
			    	// $search 	= array("\r\n", ";"	, "=", "(", ")", "{:{");
				    // $replace 	= array(''	  ,	','	, ':', ':{', "}", "{{");			    
				    // $tempData   = str_replace($search, $replace, $line);				    			
				    
				    if( $brokenline ){
				    	$line = $brokenline . $line;
				    	$brokenline = null;
						// dump($line);
						// die;
				    }

					if( substr_count($line, ",\n") || !substr_count($line, ';') ){
			    		$line   = str_replace(",\n", ',', $line);   
			    		$line   = str_replace("\n", '', $line);   
						$brokenline = $line;

						$errors[] = "Linha quebrada.";

					}
					$line   = str_replace(	['= ', ",\n", "\n", "\r"], ['=', ',', '', ''], $line);


					if( !substr_count($line, '#', 0, 1) ){

						$line = $brokenline . $line;
					}
					

		        	$item = explode('=', $line);
		        	if( !isset( $item[1] ) ){
			        	dump( $item );
			        	die;		        		
		        	}
		        	
		        	$ifc['DATA'][ $item[0] ] = $item[1];		        					        		


		        }; 
		        
		        /* ------------------------------------ */

		        
		        // LENDO QUAL SEÇÃO ?
		        if( 'DATA;' == substr( $line, 0, 5) ) {
		        	// Seção DATA
		        	$readingsection = 'DATA';
		        }else 
		        if( 'HEADER;' == substr( $line, 0, 7) ) {
		        	// Seção HEADER
		        	$readingsection = 'HEADER';
		        }
		        
		    }

		    // Fecha o arquivo
		    fclose($file);


		    // --------------------------------------------------------

		    
		    // Conversão dados
		    foreach ($ifc['DATA'] as $ponteiro => $data) {		    	

		    	$tempData = $this->convertData( $data );
				// $tempData = $this->flattenData( $tempData );

		    	// $tempData = json_encode($tempData);
		    	$ifc['DATA'][ $ponteiro ] = $tempData;
		    	
		    }

		    // print_r( json_encode( $ifc ) );
		    // die;

		    // Conversão Cabeçalho

		    foreach ($ifc['HEADER'] as $ponteiro => $data) {	
		    	
		    	$HEADERLINE = $this->convertToArray($data);

		    	if( isset($HEADERLINE['FILE_SCHEMA ']) ){
		    		$ifc['FILE_SCHEMA'] = $HEADERLINE['FILE_SCHEMA '][0][0];	   	 			
		    	}

		    	$ifc['FILE_NAME'] = basename($this->file);	   	 			
		    }
		 

		} else {
		    // error opening the file.
		} 


		$this->data = $ifc['DATA'];
		unset( $ifc['DATA'] );
		unset( $ifc['HEADER'] );

	    return $ifc;	  

	}

	/**
	 * TEJE CONVERTIDO!!!
	 * Se for um array
	 * 		Chama a função convertToArray() várias vezes
	 * Se não for array
	 * 		Chama convertToArray() uma vez
	 * @param  [type] $input [description]
	 * @return [type]        [description]
	 */
	public function convertData( $input ){
	    $out = [];
	    if( is_array($input) ){
		    foreach ($input as $key => $val) {				
	    		$out[] = $this->convertToArray($val);
		    }	    	
		    return $out;
	    }else{	    
			return $this->convertToArray($input);
	    }
    	
    }

	/**
     * Converte para Array
     * @param  [type] $value [description]
     * @return Array        [description]
     */
    public function convertToArray($value){
		
    	# "IFCPROPERTYSINGLEVALUE('Part Mark',$,IFCLABEL('M3','M3','M1','M3','M2'),$);\r\n"	    		    	

    	if( substr_count($value, '(') ){	    		
    		
    		$line 		= explode('(', $value, 2);
    		
    		//	PARAMETRO	    	
			$parameter 	= $line[0];	

    		$values = str_replace(');', '', @$line[1]); // Remove ');' 
    		$values = str_replace(';', '', $values); // Remove ';' 	    		
	    	$values = str_replace("'", '', $values); 	    	
    	
	    	
	    	# "'Part Mark',$,IFCLABEL('M3','M3','M1','M3','M2'),$"	    	
	    	
	    	
	    	// Tira e salva tudo o que há entre parênteses
	    	// 	(pra poder dar o explode na vírgula)
	    	$inParenthesis = $this->getInParenthesis( $values );
	    	
	    	$v = str_replace( $inParenthesis , '%R%', $values );
	    	

	    	# "'Part Mark',$,IFCLABEL(%R%),$\r\n"	    	
	    	
	    	$v = explode(',', $v);

	    	# ['Part Mark', $, IFCLABEL(%R%), $\r\n]"

	    	foreach ($v as $key => $val) {
	    		
	    		$val = $this->cleanLine($val);

	    		if( substr_count($val, '%R%') ){

	    			// Coloca denovo o conteúdo entre parênteses
	    			if( is_array( $inParenthesis[0] ) ){
	    				$val = str_replace( '%R%', $inParenthesis[0][0], $val );
	    			}else{
	    				$val = str_replace( '%R%', $inParenthesis[0], $val );
	    			}
							    			
					// Confere se ainda existe parenteses para convertes para array
	    			if( substr_count($val, ')') ){

		    			$valarray 	= $this->convertData( $val );				    			

						$v[ $key ] 	= $valarray;
					}else{
						$v[ $key ] 	= $val;
					}
					
	    		}else{
	    			$v[ $key ] = $val;			    			
	    		}
	    	}	    			   
	    	
	    	// $v = json_encode( $v );
	    	// $v = json_decode( $v );

	    	// Retorna Array...
	    	if( $parameter != "" ){
	    		// ...Com chave	    				
	    		return [ $parameter => $v ];	# ['IFCPROPERTYSINGLEVALUE' => ['Part Mark', $, IFCLABEL('M3','M3','M1','M3','M2'), $] ]
	    	}else{
	    		// ...Sem chave	    				
    			return $v;					# ['Part Mark', $, IFCLABEL('M3','M3','M1','M3','M2'), $]	    		
	    	}			
	 
    	}else{

    		// Não há parênteses
			
			$values = $value;
			$values = str_replace("'", '"', $values); // Remove Aspas simples e coloca aspas duplas    			    		

			return [ $values ];
		}    	

   	    

    }


	/**
	 * Remove novas linhas e parenteses excedentes
	 * @param  [type] $input [description]
	 * @return [type]        [description]
	 */
    private function cleanLine($input)
    {		    
	    $search = array("\r\n");
	    $replace = array('');

	    if( substr_count($input, ')') && !substr_count($input, '(') ){
			$search[] 	= ")";
			$replace[] 	= "";	    		
    	}
	    
	    return str_replace($search, $replace, $input);
    }


    /**
     * Extrai o que há entre parenteses
     * @param  [type] $values [description]
     * @return [type]         [description]
     */
	private function getInParenthesis($values)
	{					
		$string = $values;

		$regex = '#\((([^()]+|(?R))*)\)#';
		if (preg_match_all($regex, $string ,$matches)) {	
		    				    
		   	return $matches[1];				    	

		} else {
		    //no parenthesis
		    return NULL;
		}			

		if( substr_count($string, '(') < 2 ){
		}else {
		    //no parenthesis
		    return NULL;
		}

	}	

	private function getItem($id)
	{		
		$data = $this->data;			

		if( is_array( $id ) ){			
			return $id;
		}

		if( substr($id,0,1 ) == '#') {
			// return [ $id => $data[ $id ] ];
			return $data[ $id ];
		}
		
		return $id;
	}


	private function processArray( $input ){	 
	
		if( is_array( $input ) ) {
			foreach ($input as $key => $value) {
				if( is_array( $value ) ) {
					$input[ $key ] = $this->processArray( $value );
				}else{
					$input[ $key ] = $this->getItem( $value );						
				}					
			}
		}else{

			$input = $this->getItem($input);			

		}
		
		// $input = $this->processArray( $input );			
		
		$input = $this->flatArray( $input );			
		return $input;
	}

	private function flatArray( $input ){	 
		if( is_array($input) && count( $input ) == 1 ){			
			$input = array_shift( $input ); 			
			return $input;
		}else
		if( is_array($input) && count( $input ) > 1 ){
			foreach ($input as $key => $value) {
				$input[ $key ] = $this->flatArray( $value );
			}
		};
		return $input;
	}


	public function formated($data)
	{
		$lines = [];

		$OBJECTS = [];
		$MODELS = [];

		
		foreach ($this->data as $key => $value) {
			
			$IFCCLOSEDSHELL = $this->get( 'IFCPLATE', $value );
			if($IFCCLOSEDSHELL){
				$items[$key] = $IFCCLOSEDSHELL;
			}

			$IFCGEOMETRICREPRESENTATIONCONTEXT = $this->get( 'IFCGEOMETRICREPRESENTATIONCONTEXT', $value );
			if($IFCGEOMETRICREPRESENTATIONCONTEXT){
				// dump( $IFCGEOMETRICREPRESENTATIONCONTEXT );
				$GRC[$key] = $IFCGEOMETRICREPRESENTATIONCONTEXT;
			}

		}	

		
		foreach ($GRC as $key => $value) {
			$IFCLOCALPLACEMENT 		= $this->getItem( $value['IFCGEOMETRICREPRESENTATIONCONTEXT'][4] );
			$IFCAXIS2PLACEMENT3D	= $IFCLOCALPLACEMENT['IFCAXIS2PLACEMENT3D'];

			foreach ($IFCAXIS2PLACEMENT3D as $id => $content) {
				$IFCCARTESIANPOINT = $this->getItem( $content );
				$IFCAXIS2PLACEMENT3D[ $id ] = $this->processArray( $IFCCARTESIANPOINT );

				$GRC[ $key ] = $IFCAXIS2PLACEMENT3D;
			}

		}		
		$lines['GEOREPCONTEXT'] = $this->processArray( $GRC );

		foreach ($items as $key => $value) {	

			// dump( $value );

			$lines['OBJECTS'][] 			= $value['IFCPLATE'][5];
			$OBJECTS['KEY'][]		 		= $value['IFCPLATE'][5];
			$OBJECTS['HANDLE'][] 			= $value['IFCPLATE'][4];
			$OBJECTS['MODEL'][] 			= $key;
			
			$MODELS['KEY'][ $key ]		= $value['IFCPLATE'][6]; 
			$MODELS['HANDLE'][ $key ] 	= $value['IFCPLATE'][4]; 
			
		}

		// exit;
		
		#64, #65...
		foreach ( $OBJECTS['KEY'] as $key => $value) {
			$IFCLOCALPLACEMENT 		= $this->getItem( $value );

			#64=IFCLOCALPLACEMENT($,#49);

			$IFCAXIS2PLACEMENT3D 	= $this->getItem( $IFCLOCALPLACEMENT[ 'IFCLOCALPLACEMENT' ][1] );
			$IFCAXIS2PLACEMENT3D 	= $IFCAXIS2PLACEMENT3D[ 'IFCAXIS2PLACEMENT3D' ];

			#49=IFCAXIS2PLACEMENT3D(#14,#11,#12);
			$COORD 			= $this->getItem( $IFCAXIS2PLACEMENT3D[0] );
			$IFCDIRECTIONZ	= $this->getItem( $IFCAXIS2PLACEMENT3D[1] );
			$IFCDIRECTIONX	= $this->getItem( $IFCAXIS2PLACEMENT3D[2] );


			$lines['OBJECTS'][ $key ] 					= [];
			$lines['OBJECTS'][ $key ]['MODEL'] 			= $OBJECTS['MODEL'][ $key ];
			$lines['OBJECTS'][ $key ]['HANDLE'] 			= $OBJECTS['HANDLE'][ $key ];
			$lines['OBJECTS'][ $key ]['TYPE'] 			= 'IFCPLATE';
			$lines['OBJECTS'][ $key ]['COORD'] 			= $COORD['IFCCARTESIANPOINT'][0];
			$lines['OBJECTS'][ $key ]['IFCDIRECTIONZ']	= $IFCDIRECTIONZ['IFCDIRECTION'][0];
			$lines['OBJECTS'][ $key ]['IFCDIRECTIONX']	= $IFCDIRECTIONX['IFCDIRECTION'][0];
			
		}

	
		#97, #98...
		foreach ( $MODELS['KEY'] as $key => $value) {
			
			$IFCSHAPEREPRESENTATION 			= $this->getItem( $value );
			
			#97=IFCPRODUCTDEFINITIONSHAPE('35FF1E64FB494B70BF92E085DC4D9895',$,(#95));
			
			$IFCPRODUCTDEFINITIONSHAPE 			= $this->getItem( $IFCSHAPEREPRESENTATION['IFCPRODUCTDEFINITIONSHAPE'][2][0] );

			#95=IFCSHAPEREPRESENTATION(#67,'Body','Brep',(#91));

			$IFCGEOMETRICREPRESENTATIONCONTEXT	= $this->getItem( $IFCPRODUCTDEFINITIONSHAPE['IFCSHAPEREPRESENTATION'][3][0] );

			#91=IFCFACETEDBREP(#87);

			$IFCFACETEDBREP						= $this->getItem( $IFCGEOMETRICREPRESENTATIONCONTEXT['IFCFACETEDBREP'][0] );
			$IFCCLOSEDSHELL						= $IFCFACETEDBREP['IFCCLOSEDSHELL'][0];

			#87=IFCCLOSEDSHELL((#73,#74,#75,#76,#77,#78));
			
			foreach ($IFCCLOSEDSHELL as $ke => $val) {
				$IFCFACE = $this->getItem( $val );
				$IFCFACEOUTERBOUND 	= $this->getItem( $IFCFACE['IFCFACE'][0][0] );
				
				$IFCPOLYLOOP 		= $this->getItem( $IFCFACEOUTERBOUND['IFCFACEOUTERBOUND'][0] );
				$IFCPOLYLOOP 		= $IFCPOLYLOOP['IFCPOLYLOOP'][0];				

				foreach ($IFCPOLYLOOP as $k => $v) {
					# code...
					// $IFCPOLYLOOP['IFCPOLYLOOP'][0][ $k ] 	= $this->getItem( $v ); 
					$IFCPOLYLOOP[ $k ] = $this->getItem( $v ); 

					$IFCCARTESIANPOINT = [];
					foreach ($IFCPOLYLOOP[ $k ] as $valor) {
						$IFCCARTESIANPOINT[] = $valor[0];
						// $IFCPOLYLOOP[ $k ][] = $valor[0];
						// dump($chave);
					}

					$IFCPOLYLOOP[ $k ] = $IFCCARTESIANPOINT;

				}
				
				$IFCCLOSEDSHELL[ $ke ] = $IFCPOLYLOOP;

			}

			$lines['MODELS'][ $key ] = $IFCCLOSEDSHELL;

			#48=IFCAXIS2PLACEMENT3D(#13,#11,#12);

			// $IFCCARTESIANPOINT					= $IFCAXIS2PLACEMENT3D['IFCAXIS2PLACEMENT3D']; // #13,#11,#12

			// // #13, #11, #12
			// $normal 		= $this->getItem( $IFCCARTESIANPOINT[0] ); 	#13
			// $normalY 	= $this->getItem( $IFCCARTESIANPOINT[1] );	#11	
			// $normalZ 	= $this->getItem( $IFCCARTESIANPOINT[2] );	#12

			// $lines['MODELS'][ $key ] = []; // EMPTY ARRAY
			// // TIPO: PLATE, COLUMN...
			// $lines['MODELS'][ $key ][ 'IFCPLATE' ] 						= []; // EMPTY ARRAY
			// $lines['MODELS'][ $key ][ 'IFCPLATE' ][ 'HANDLE' ] 			= $MODELS['HANDLE'][ $key ]; 	
			// $lines['MODELS'][ $key ][ 'IFCPLATE' ][ 'COORD' ] 			= $normal['IFCCARTESIANPOINT'][0]; 	#13
			// $lines['MODELS'][ $key ][ 'IFCPLATE' ][ 'IFCDIRECTIONY' ] 	= $normalY['IFCDIRECTION'][0];	 	#11
			// $lines['MODELS'][ $key ][ 'IFCPLATE' ][ 'IFCDIRECTIONZ' ]	= $normalZ['IFCDIRECTION'][0]; 		#12

		}

		// dump( $lines );						
		// die;
					
		return $lines;

	}


	public function get($search='', $content )
	{
		// $entry = array_keys($content);
		// dump($content);
		if( isset( $content[ $search ] ) ){
			$return = [ key($content) =>  $content[ key($content) ] ];
			return $return;
		}
	}
	
	public function flattenData(array $array) {
	    $return = array();
	    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
	    return $return;
	}

}