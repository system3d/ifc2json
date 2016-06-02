<?php

namespace System3D\IFC2JSON;

/**
 * Converte as coisa
 */
class IFC2JSON
{

	var $file,
		$advanced;

	function __construct($file = null, $advanced = false)
	{		
		$this->file 	= $file;		
		$this->advanced 	= $advanced;		
	}


	/**
	 * [convert description]
	 * @return [type] [description]
	 */
    public function convertFile( $file = null )
    {
    	if( is_file($file) ){
			return $file . ' Ta convertido!';    	    	
    	} else
    	if( $this->file ){    		
    		return $this->file . ' Ta convertido!';    	    		
    	}
    	
    	return 'Converter o que?';
    	
    }

    /**
     * [getJson description]
     * @param  [type] $file [description]
     * @return [type]       [description]
     */
    public function getJson( $file = null )
    {
    	if ( is_file($file) ) {
    		return $this->readIFC( $file );
    		// return 'Convertendo e cuspindo json do arquivo '.$file.'...';
    	}
    	
    	if (!$this->file) {
    		return 'Informe o arquivo';
    	}
    	
    	$data = $this->readIFC( $this->file );	
    	
    	if( $this->advanced ){
    		$minified 	= $this->advanced( $data, 8 );	
    		$data = $minified;
    	}
    	
    	dump( $data );
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
    	
    	if( $this->advanced ){
    		$minified 	= $this->advanced( $data, 8 );	
    		$data = $minified;
    	}
    	
    	$data = json_encode( $data );


    	// rename file
    	$filname = str_replace('.ifc.ifc', '.ifc', $this->file);
    	$filname = str_replace('.ifc', '.json', $filname);

    	header('Content-disposition: attachment; filename='.$filname);
		header('Content-type: application/json');
		echo $data;
		die;
    }


    public function readIFC( $file )
    {    
    	if( !is_file( $file ) )
    		return "Arquivo não encontrado!";    	

    	$handle = fopen( $file, "r");
		if ($handle) {

			$readingsection = '';

			$ifc = [];

		    while (($line = fgets($handle)) !== false) {        

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

		        	$item = explode('=', $line);
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
		    fclose($handle);


		    // --------------------------------------------------------

		    
		    // Conversão dados
		    foreach ($ifc['DATA'] as $ponteiro => $data) {		    	
		    	$ifc['DATA'][ $ponteiro ] = $this->convertData( $data );
		    }
		    // Conversão Cabeçalho
		    foreach ($ifc['HEADER'] as $ponteiro => $data) {		    	
		    	$ifc['HEADER'][ $ponteiro ] = $this->convertHeader( $data );
		    }
		 

		} else {
		    // error opening the file.
		} 


		// $ifc = json_encode( $ifc );
	   	   
	    return  $ifc;	  

	}

	/**
	 * TEJE CONVERTIDO!!!
	 * Se for um array
	 * 		Chama a função convert() várias vezes
	 * Se não for array
	 * 		Chama convert() uma vez
	 * @param  [type] $input [description]
	 * @return [type]        [description]
	 */
	public function convertData( $input ){
	    $out = [];
	    if( is_array($input) ){
		    foreach ($input as $key => $val) {				
	    		// print_r($val);
		    	$out[] = $this->convert($val);
		    }	    	
		    return $out;
	    }else{
			return $this->convert($input);
	    }

    	
    }


    public function convertHeader( $input ){
    	if( substr_count($input, 'FILE_SCHEMA') ){
    		$input = $this->getInParenthesis( $input[0] );
    		return $this->getInParenthesis( $input[0] );
    	}
    	return NULL;
    }

	/**
     * Converte para Array
     * @param  [type] $value [description]
     * @return Array        [description]
     */
    public function convert($value){
		
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

	/**
	 * [advanced description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function advanced($data, $levels = 5)
	{

		$this->data = $data['DATA'];
		$data = $this->data;	

		for ($level = 0; $level < $levels; $level++) { 	
			$data = $this->processArray( $data );	
		}

		
		$output = [];

		$output[ 'FILENAME' ] = realpath( $this->file );

		foreach ($data as $selector => $content) {
						
			if( isset( $content['IFCPLATE'] ) ){				

				// $output[ 'IFCCLOSEDSHELL' ]['HANDLE'] 	= $content['IFCPLATE'][4];
				// $output[ 'IFCPLATE' ] 					= $content['IFCPLATE'][5];

			}

			if( isset( $content['IFCAPPLICATION'] ) ){				
				// $output[ 'IFCAPPLICATION' ]	= $content['IFCAPPLICATION'][2];
			}

			if( isset( $content['IFCCLOSEDSHELL'][0] ) ){			
				// dump( $content['IFCCLOSEDSHELL'] );
				// die;
				$output[ 'IFCCLOSEDSHELL' ][$selector] = $content['IFCCLOSEDSHELL'][0];				
			}
		}
		
		return $output;

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

		return $input;
	}

}