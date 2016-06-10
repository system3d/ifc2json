<?php

namespace System3D\IFC2JSON;

/**
 * Converte as coisa
 */
class IFC2JSON
{

	var $file,
		$data,
		$points,
		$vertices,
		$faces,
		$formated;

	function __construct($file = null, $formated = false)
	{		
		if (!is_file($file) ) {
			return "Informe o arquivo";
		}

		$this->file 	= $file;
		$this->formated = $formated;
		$this->data;		
		$this->points 	= [];		
		$this->vertices	= [];		
		$this->faces	= [];		
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
    		
    		$data['APP'] = $formated['IFCAPPLICATION'];
    		$data['G'] = $formated['GEOREPCONTEXT'];
    		$data['P'] = $formated['POINTS'];    	
    		$data['V'] = $formated['VERTICES'];
    		$data['F'] = $formated['FACES'];
    		$data['O'] = $formated['OBJECTS'];
    		$data['M'] = $formated['MODELS'];
    	}
    	    	   	    	    
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

    		$data['APP'] = $formated['IFCAPPLICATION'];
    		$data['G'] = $formated['GEOREPCONTEXT'];
    		$data['P'] = $formated['POINTS'];
    		$data['V'] = $formated['VERTICES'];
    		$data['F'] = $formated['FACES'];
    		$data['O'] = $formated['OBJECTS'];
    		$data['M'] = $formated['MODELS'];
    	}


    	// dump( $data );
    	// die;

    	$data = json_encode( $data );


    	// rename file
    	$filename = str_replace('.ifc.ifc', '.ifc', $this->file);
    	$filename = str_replace('.ifc', '.json', $filename);

		// put a timestamp
		$filename = explode('.', $filename);
		$name = $filename;
		unset( $name[ count($name) -1 ] );

		$timestamp = date('Y.m.d_H.i.s');
		$filename = implode('', $name) . "_" . $timestamp . "." . end($filename);
		$filename = basename($filename);    		

    	header('Content-disposition: attachment; filename='.$filename);
		header('Content-type: application/json');
		echo $data;
		exit;
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
				    }

					if( substr_count($line, ",\n") || !substr_count($line, ';') ){
			    		$line   = str_replace(",\n", ',', $line);   
			    		$line   = str_replace("\n", '', $line);   
						$brokenline = $line;

						$errors[] = "Linha quebrada!";

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

		    	// $tempData = json_encode($tempData);
		    	$ifc['DATA'][ $ponteiro ] = $tempData;
		    	
		    }
		   

		    // Conversão Cabeçalho
		    foreach ($ifc['HEADER'] as $ponteiro => $data) {	
		    	
		    	$HEADERLINE = $this->convertToArray($data);

		    	if( isset($HEADERLINE['FILE_SCHEMA ']) ){
		    		$ifc['SCHEMA'] = $HEADERLINE['FILE_SCHEMA '][0][0];	   	 			
		    	}

		    	$ifc['NAME'] = basename($this->file);	   	 			
		    }
		 

		} else {
		    // error opening the file.
		} 


		$this->data = $ifc['DATA'];
		unset( $ifc['DATA'] );   	// DADOS COMPLETOS
		unset( $ifc['HEADER'] );	// CABEÇALHOS ORIGINAIS (php array)

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
		$lines 					= [];
		$items 					= [];
		$buildingelementproxy 	= [];

		$OBJECTS	= [];
		$POINTS		= [];
		$MODELS 	= [];
		
		foreach ($this->data as $key => $value) {
			
			// IFCPLATE
			if( isset($value['IFCPLATE']) ){
				$IFCCLOSEDSHELL = $this->get( 'IFCPLATE', $value );
				if($IFCCLOSEDSHELL){
					$items[$key] = $IFCCLOSEDSHELL;
				}
			}


			// IFCCOLUMN
			if( isset($value['IFCCOLUMN']) ){
				$IFCCLOSEDSHELL = $this->get( 'IFCCOLUMN', $value );
				if($IFCCLOSEDSHELL){
					$items[$key] = $IFCCLOSEDSHELL;
				}
			}


			// IFCBEAM
			if( isset($value['IFCBEAM']) ){
				$IFCCLOSEDSHELL = $this->get( 'IFCBEAM', $value );
				if($IFCCLOSEDSHELL){
					$items[$key] = $IFCCLOSEDSHELL;
				}
			}


			// IFCGEOMETRICREPRESENTATIONCONTEXT
			if( isset($value['IFCGEOMETRICREPRESENTATIONCONTEXT']) ){
				$IFCGEOMETRICREPRESENTATIONCONTEXT = $this->get( 'IFCGEOMETRICREPRESENTATIONCONTEXT', $value );
				if($IFCGEOMETRICREPRESENTATIONCONTEXT){
					$GRC[$key] = $IFCGEOMETRICREPRESENTATIONCONTEXT;
				}
			}


		    // IFCAPPLICATION
			if( isset($value['IFCAPPLICATION']) ){
			    $IFCAPPLICATION = $this->get( 'IFCAPPLICATION', $value );
			    if($IFCAPPLICATION){
			    	$IFCAPPLICATION = $IFCAPPLICATION['IFCAPPLICATION'][2]; // $IFCAPPLICATION NÃO É ARRAY INTENCIONALMENTE, POIS SÓ PRECISA DE UM
			    };						    
			}
		    

		    // IFCBUILDINGELEMENTPROXY
			if( isset($value['IFCBUILDINGELEMENTPROXY']) ){
			    $IFCBUILDINGELEMENTPROXY = $this->get( 'IFCBUILDINGELEMENTPROXY', $value );			    
			    if($IFCBUILDINGELEMENTPROXY){
			    	$buildingelementproxy[ $key ] = $IFCBUILDINGELEMENTPROXY;
			    };						    
			}
		    
		}		

		$lines['IFCAPPLICATION'] = $IFCAPPLICATION;

		
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

		// ...
		
		// ITEMS
		foreach ($items as $key => $value) {	
			
			$OBJECTS['TYPE'][] 					= key( $value );			

			$entry 								= reset($value);			

			$lines['OBJECTS'][] 				= $entry[5];
			$OBJECTS['KEY'][]		 			= $entry[5];
			$OBJECTS['HANDLE'][] 				= $entry[4];
			$OBJECTS['MODEL'][] 				= $key;			
			$MODELS['KEY'][ $key ]				= $entry[6]; 
			$MODELS['HANDLE'][ $key ] 			= $entry[4]; 
			
		}
		

		if( isset($OBJECTS['KEY']) ){
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


				$lines['OBJECTS'][ $key ] 	   = [];
				$lines['OBJECTS'][ $key ]['M'] = $OBJECTS['MODEL'][ $key ];
				$lines['OBJECTS'][ $key ]['H'] = $OBJECTS['HANDLE'][ $key ];
				$lines['OBJECTS'][ $key ]['T'] = $this->replaceType( $OBJECTS['TYPE'][ $key ] );
				
				//FORMATA NÚMEROS
				$COORD['IFCCARTESIANPOINT'][0]		= array_map('intval', $COORD['IFCCARTESIANPOINT'][0]);
				$IFCDIRECTIONZ['IFCDIRECTION'][0]	= array_map('intval', $IFCDIRECTIONZ['IFCDIRECTION'][0]);
				$IFCDIRECTIONX['IFCDIRECTION'][0]	= array_map('intval', $IFCDIRECTIONX['IFCDIRECTION'][0]);

				// SALVA POINTS
				$COORD['IFCCARTESIANPOINT'][0]		= array_map( array($this, 'savePoint'), $COORD['IFCCARTESIANPOINT'][0]);
				$IFCDIRECTIONZ['IFCDIRECTION'][0]	= array_map( array($this, 'savePoint'), $IFCDIRECTIONZ['IFCDIRECTION'][0]);
				$IFCDIRECTIONX['IFCDIRECTION'][0]	= array_map( array($this, 'savePoint'), $IFCDIRECTIONX['IFCDIRECTION'][0]);

				$vertice 	= implode(',', $COORD['IFCCARTESIANPOINT'][0] );
				$vertice 	= $this->saveVertice( $vertice );			
				$lines['OBJECTS'][ $key ]['C'] 	= $vertice;

				// $lines['OBJECTS'][ $key ]['Z']		= implode(',', $IFCDIRECTIONZ['IFCDIRECTION'][0] );
				// $lines['OBJECTS'][ $key ]['X']		= implode(',', $IFCDIRECTIONX['IFCDIRECTION'][0] );
				$verticez = implode(',', $IFCDIRECTIONZ['IFCDIRECTION'][0] );
				$verticez = $this->saveVertice( $verticez );

				$verticex = implode(',', $IFCDIRECTIONX['IFCDIRECTION'][0] );
				$verticex = $this->saveVertice( $verticex );

				$lines['OBJECTS'][ $key ]['D'] = [$verticez, $verticex];
				
			}

		}

		if( isset($MODELS['KEY']) ){
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

							$formattedNumber = [];
							
							//FORMATA NÚMEROS
							$formattedNumber = array_map('intval', $valor[0]);
							$formattedNumber = array_map( array($this, 'savePoint'), $formattedNumber);								
							
							$vertice = implode(',', $formattedNumber);
							$vertice = $this->saveVertice( $vertice );
							$IFCCARTESIANPOINT[] = $vertice;

						}

						$IFCPOLYLOOP[ $k ] = $this->processArray( $IFCCARTESIANPOINT );

					}
					
					$face = $this->saveFace( $IFCPOLYLOOP );					
					$IFCCLOSEDSHELL[ $ke ] = $face;

				}

				$lines['MODELS'][ $key ] = $IFCCLOSEDSHELL;

			}

		}


		/**
		 * 	PARAFUSOS
		 */
		$PARAFUSOS = [];
		foreach ($buildingelementproxy as $key => $value) {	
			
			$OBJECTS['TYPE'][] 					= key( $value );			

			$entry 								= reset($value);			

			$lines['OBJECTS'][] 				= $entry[5];
			$OBJECTS['KEY'][]		 			= $entry[5];
			$OBJECTS['HANDLE'][] 				= $entry[4];
			$OBJECTS['MODEL'][] 				= '';			
			// $MODELS['KEY'][ $key ]				= $entry[6]; 
			// $MODELS['HANDLE'][ $key ] 			= $entry[4]; 
			
		}

		if( isset($OBJECTS['KEY']) ){
			foreach ($OBJECTS['KEY'] as $key => $value) {	

				$IFCLOCALPLACEMENT 		= $this->getItem( $value );
				$IFCAXIS2PLACEMENT3D 	= $this->getItem( $IFCLOCALPLACEMENT[ 'IFCLOCALPLACEMENT' ][1] );
				$IFCAXIS2PLACEMENT3D 	= $IFCAXIS2PLACEMENT3D[ 'IFCAXIS2PLACEMENT3D' ];

				#49=IFCAXIS2PLACEMENT3D(#14,#11,#12);
				
				$COORD 			= $this->getItem( $IFCAXIS2PLACEMENT3D[0] );


				$IFCDIRECTIONZ	= $this->getItem( $IFCAXIS2PLACEMENT3D[1] );
				$IFCDIRECTIONX	= $this->getItem( $IFCAXIS2PLACEMENT3D[2] );


				$lines['OBJECTS'][ $key ] 	   = [];
				$lines['OBJECTS'][ $key ]['M'] = $OBJECTS['MODEL'][ $key ];
				$lines['OBJECTS'][ $key ]['H'] = $OBJECTS['HANDLE'][ $key ];
				$lines['OBJECTS'][ $key ]['T'] = $this->replaceType( $OBJECTS['TYPE'][ $key ] );
				
				// Formata números
				$COORD['IFCCARTESIANPOINT'][0]		= array_map('intval', $COORD['IFCCARTESIANPOINT'][0]);
				$IFCDIRECTIONZ['IFCDIRECTION'][0]	= array_map('intval', $IFCDIRECTIONZ['IFCDIRECTION'][0]);
				$IFCDIRECTIONX['IFCDIRECTION'][0]	= array_map('intval', $IFCDIRECTIONX['IFCDIRECTION'][0]);

				// SALVA POINTS
				$COORD['IFCCARTESIANPOINT'][0]		= array_map( array($this, 'savePoint'), $COORD['IFCCARTESIANPOINT'][0]);
				$IFCDIRECTIONZ['IFCDIRECTION'][0]	= array_map( array($this, 'savePoint'), $IFCDIRECTIONZ['IFCDIRECTION'][0]);
				$IFCDIRECTIONX['IFCDIRECTION'][0]	= array_map( array($this, 'savePoint'), $IFCDIRECTIONX['IFCDIRECTION'][0]);


				$vertice = implode(',', $COORD['IFCCARTESIANPOINT'][0] );
				$vertice = $this->saveVertice( $vertice );
				$lines['OBJECTS'][ $key ]['C'] 	= $vertice;

				$verticez = implode(',', $IFCDIRECTIONZ['IFCDIRECTION'][0] );
				$verticez = $this->saveVertice( $verticez );

				$verticex = implode(',', $IFCDIRECTIONX['IFCDIRECTION'][0] );
				$verticex = $this->saveVertice( $verticex );

				$lines['OBJECTS'][ $key ]['D'] = [$verticez, $verticex];
											
			}
		}


		// Format ints
		foreach ($lines['GEOREPCONTEXT'] as $key => $value) {
			$lines['GEOREPCONTEXT'][ $key ] = array_map('intval', $value);
			$lines['GEOREPCONTEXT'][ $key ] = array_map( array($this, 'savePoint'), $lines['GEOREPCONTEXT'][ $key ]);

			// dump( $lines['GEOREPCONTEXT'][ $key ] );						

			$vertice = implode(',', $lines['GEOREPCONTEXT'][ $key ]);

			$lines['GEOREPCONTEXT'][ $key ] = $this->saveVertice( $vertice );
			
			// $lines['GEOREPCONTEXT'][ $key ] = $vertice;
		}




		$lines['POINTS'] 	= $this->points;
		$lines['VERTICES'] 	= $this->vertices;
		$lines['FACES']		= $this->faces;
					
		return $lines;

	}


	public function get($search='', $content )
	{
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

	public function savePoint($point)
	{		
		if( in_array($point, $this->points) ){
			return array_search($point, $this->points);
		}else{
			array_push($this->points, $point);
			return array_search($point, $this->points);
		}
	}


	public function saveVertice($vertice)
	{				
		if( in_array($vertice, $this->vertices) ){
			return array_search($vertice, $this->vertices);
		}else{
			array_push($this->vertices, $vertice);
			return array_search($vertice, $this->vertices);
		}
	}


	public function saveFace($face)
	{		
		if( in_array($face, $this->faces) ){
			return array_search($face, $this->faces);
		}else{
			array_push($this->faces, $face);
			return array_search($face, $this->faces);
		}
	}


	public function replaceType($string)
	{		
		$newstring = "";

		switch ($string) {
			case 'IFCPLATE':
				$newstring = "CH";
				break;
			
			case 'IFCBUILDINGELEMENTPROXY':
				$newstring = "PF";
				break;
			
			case 'IFCBEAM':
				$newstring = "VG";
				break;
			
			case 'IFCCOLUMN':
				$newstring = "PI";
				break;
			
			default:
				$newstring = $string;
				break;
		}

		return $newstring;
	}

}