<?php


add_filter('caldera_forms_view_field_checkbox', 'cf_handle_multi_view', 10, 3);
function cf_handle_multi_view( $data, $field ){

	if( empty( $data ) || !is_array( $data ) ){
		return $data;
	}
	// can put in the value as well.
	$viewer = array();

	foreach( $data as $key=>$value ){

		foreach( $field['config']['option'] as $option_key=>$option ){
			if( $value == $option['value'] ){
				$viewer[$key] = $option['label'] . ' (' . $option['value'] . ')';
			}
		}
		if( !isset( $viewer[$key] ) ){
			$viewer[$key] = $value;
		}
		
	}
	return implode( ', ', $viewer );

}


add_filter('caldera_forms_process_field_file', 'cf_handle_file_upload', 10, 3);
add_filter('caldera_forms_process_field_advanced_file', 'cf_handle_file_upload', 10, 3);


function cf_handle_file_upload( $entry, $field, $form ){

	// check transdata if string based entry
	if( is_string( $entry ) ){
		$transdata = get_transient( $entry );
		if( !empty( $transdata ) ){
			return $transdata;
		}
	}

	if( isset($_POST[ '_cf_frm_edt' ] ) ) {
		if ( ! isset( $_FILES )
		     || ( isset( $_FILES[ $field[ 'ID' ] ][ 'size' ][0] ) && 0 == $_FILES[ $field[ 'ID' ] ][ 'size' ][0] )
			|| ( isset( $_FILES[ $field[ 'ID' ] ][ 'size' ] ) && 0 == $_FILES[ $field[ 'ID' ] ][ 'size' ]  )
		) {
			$entry = Caldera_Forms::get_field_data( $field[ 'ID' ], $form, absint( $_POST[ '_cf_frm_edt' ] ) );

			return $entry;
		}
	}
	$required = false;
	if ( isset( $field[ 'required' ] ) &&  $field[ 'required' ] ){
		$required = true;
	}
	if(!empty($_FILES[$field['ID']]['size'])){
		// check is allowed 
		if(!empty($field['config']['allowed'])){
			$types = explode(',',$field['config']['allowed']);

			foreach($types as &$type){
				$type = trim( trim( $type,'.' ) );
			}
			foreach( (array) $_FILES[$field['ID']]['name'] as $file_name ){
				if( empty( $file_name ) ){
					return $entry;
				}
				$check = pathinfo( $file_name );
				if(!in_array( $check['extension'], $types)){
					if(count($types) > 1){
						return new WP_Error( 'fail', __('File type not allowed. Allowed types are: ', 'caldera-forms') . ' '. implode(', ', $types) );
					}else{
						return new WP_Error( 'fail', __('File type needs to be', 'caldera-forms') . ' .' . $types[0] );					
					}
				}
			}

		}
		if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
		
		$files = array();
		foreach( (array) $_FILES[$field['ID']] as $file_key=>$file_parts ){
			foreach( (array) $file_parts as $part_index=>$part_value ){
				$files[ $part_index ][ $file_key ] = $part_value;
			}
		}

		$uploads = array();
		foreach( $files as $file ){
			if( ! $required && 0 == $file[ 'size' ] ){
				continue;
			}
			$upload = wp_handle_upload($file, array( 'test_form' => false ), date('Y/m') );

			if( !empty( $upload['error'] ) ){
				return new WP_Error( 'fail', $upload['error'] );
			}
			$uploads[] = $upload['url'];
		}

		if( count( $uploads ) > 1 ){
			return $uploads;
		}

		if( empty( $uploads ) ){
			return array();
		}

		return $uploads[0];
	}else{
		// for multiples
		if( is_array( $entry ) ){
			foreach( $entry as $index => $line ){
				if( !filter_var( $line, FILTER_VALIDATE_URL ) ){
					unset( $entry[ $index ] );
				}
			}
			return $entry;
		}else{
			if( filter_var( $line, FILTER_VALIDATE_URL ) ){
				return $entry;
			}
		}

	}

}
