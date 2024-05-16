<?php

add_filter( 'wpcf7_validate_select*', 'validate_select', 20, 2 );
  
function validate_select( $result, $tag ) {
	if ( $tag->name == 'INPUT_NAME' ) {
		$isset = isset($_POST['INPUT_NAME']);
		if ( !$isset || $_POST['INPUT_NAME'] == 'WARTOŚĆ' ) {
			$result->invalidate( $tag, "Należy wybrać rodzaj działalności." );
		}
	} 
	return $result;
}