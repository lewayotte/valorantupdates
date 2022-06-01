<?php
	
error_reporting( E_ERROR | E_WARNING );
	
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable( __DIR__ );
$dotenv->load();

$db_dir = __DIR__ . '/db';

global $val_db;
$val_db = new \SleekDB\Store( 'updates', $db_dir );

if ( $update = get_latest_game_update() ) {
	
	if ( has_new_content( $update ) ) {
		
		send_to_discord( $update );
		
	}
	
}

function get_latest_game_update() {
	
	$contents = file_get_contents( 'https://playvalorant.com/page-data/en-us/news/game-updates/page-data.json' );
	
	if ( $content = json_decode( $contents ) ) {
		
		$payload = array(
			'article_id' => $content->result->pageContext->data->articles[0]->id,
			'content'    => 'https://playvalorant.com/en-us' . $content->result->pageContext->data->articles[0]->url->url,
		);
			
		return $payload;

	}
	
	return false;
	
}

function has_new_content( $update ) {
	
	global $val_db;
	
	if ( $val_db->findOneBy( [ 'article_id', '=', $update['article_id'] ] ) ) {
		
		return false;
		
	} else {
		
		$val_db->insert( $update );
		
	}

	return true;
	
}

function send_to_discord( $payload ) {
		
	$curl = curl_init();
	
	// How to Setup a Discord Webhook: https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks
	curl_setopt_array( $curl, 
		array(
			CURLOPT_URL            => $_ENV['DISCORD_WEBHOOK'],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_POSTFIELDS     => $payload,
		)
	);
	
	$response = curl_exec( $curl ) ;
	curl_close( $curl );
	
	return $response;
	
}
