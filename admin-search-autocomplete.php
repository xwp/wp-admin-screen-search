<?php

$tags = array(
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		'th',
		'label',
		'td',
		'a',
		'strong',
		'em',
		'p',
		'span'
	);

$user_ID = get_current_user();

$args = array(
	'author'     => $user_ID,
	'post_type'  => 'search_index',
);
$posts = get_posts( $args );

$strings = array();
// For each post, get all tags values saved in post meta and save to array
foreach ( $posts as $post ) {
	$post_ID = $post->ID;
	foreach( $tags as $tag ) {
		$post_meta = get_post_meta( $post_ID, $tag, true );
		if ( is_array( $post_meta ) {
			foreach( $post_meta as $string ) {
				$strings[] = $post_meta;
			}
		} else {
			$strings[] = $post_meta;
		}
	}
}

// Assemble the Response
$response = '[';
$first = true;
foreach ( $strings as $string ) {
	// Insert a comma between values
	if ( ! $first ) {
		$response .=  ',';
	} else {
		$first = false;
	}
	$response .= '{"value":"' . $string . '"}';
}
$response .= ']';

echo json_encode( $response );
