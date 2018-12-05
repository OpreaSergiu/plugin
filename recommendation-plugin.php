<?php
/**
* Plugin Name: Recommendation Products
* Plugin URI: http://dsltd.tk
* Description: Recommendation plugin based on user ratings
* Version: 1.0
* Author: Oprea Sergiu
* Author URI: http://dsltd.tk
**/

function getRecommendedProducts()
{
	$servername = "xxxxxxxxxx";
    $username = "xxxxxxxxxx";
    $password = "xxxxxxxxxx";
    $dbname = "xxxxxxxxxx";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 
    else
    {
        echo "<p id='message'>Connected successfully</p>";
    }
    
    $sql_ids = "SELECT ID FROM `wp_posts` WHERE post_status = 'publish' AND post_type = 'product'";
    $result_ids = $conn->query($sql_ids);
    
    $sql_users = "SELECT ID FROM `wp_users`";
    $result_users = $conn->query($sql_users);
    
	$rating_matrix = array();
	$products_ids = array();
	$users_ids = array();
	$i = 0;
	
	while($row = $result_ids->fetch_assoc())
	{
	    $products_ids[$i] = $row["ID"];
	    $i = $i + 1;
	}
	
	$i = 0;
	while($row1 = $result_users->fetch_assoc())
	{
		$users_ids[$i] = $row1["ID"];
		$i = $i + 1;
	}
	
	
	foreach($users_ids as $users)
	{
		foreach($products_ids as $products)
		{
			
			$rating_matrix[$users][$products] = 0;
		}
	}
	
	$sql_rating = ("SELECT user_id, comment_post_ID, meta_value FROM `wp_comments`
				    INNER JOIN wp_commentmeta ON wp_comments.comment_ID = wp_commentmeta.comment_id
				    WHERE comment_type = 'review' AND meta_key = 'rating' AND comment_approved = '1'");
				    
	$result_rating = $conn->query($sql_rating);

	while($row = $result_rating->fetch_assoc())
	{
		$a = $row["user_id"];
		$b = $row["comment_post_ID"];
		$c = $row["meta_value"];
		echo "<p id='message'>$a $b $c</p>";
		
		$rating_matrix[$row["user_id"]][$row["comment_post_ID"]] = $row["meta_value"];
	}
	
	foreach($rating_matrix as $line)
	{
		foreach($line as $column)
		{
			echo $column.' ';
		}
		echo '</br>';
	}
	
	$comparison = array();
	
	foreach($products_ids as $p1)
	{
		foreach($products_ids as $p2)
		{
			$s = 0;
			$module_v1 = 0;
			$module_v2 = 0;
			foreach($users_ids as $u)
			{
				$s+= $rating_matrix[$u][$p1]*$rating_matrix[$u][$p2];
				$module_v1+= $rating_matrix[$u][$p1]*$rating_matrix[$u][$p1];
				$module_v2+= $rating_matrix[$u][$p2]*$rating_matrix[$u][$p2];
			}
			
			$comparison[$p1][$p2] = $s/(sqrt($module_v1*$module_v2)); 
		}
	}
	
	foreach($comparison as $line)
	{
		foreach($line as $column)
		{
			echo number_format((float)$column, 2, '.', '').' ';
		}
		echo '</br>';
	}	
	
	$current_user_id = get_current_user_id();
	
	$rated_products = array();
	
	foreach($products_ids as $products)
	{
		if($rating_matrix[$current_user_id][$products->ID] != 0)
		{
			$rated_products[$products] = $products;
		}
	}
	$unrated_products = array();
	
	foreach($products_ids as $products)
	{
		if($rating_matrix[$current_user_id][$products] == 0)
		{
			$s=0;
			$norm=0;
			foreach($rated_products as $id)
			{
				$s+= $rating_matrix[$current_user_id][$id] * $comparison[$products][$id];
				$norm+= $comparison[$products][$id];
			}
			
			$unrated_products[$products] = $s/$norm;
		}
	}
	sort($unrated);
	$nr=1;
	$string_ids='';
	foreach($unrated_products as $key => $unrated)
	{
		if($nr<=1)
		{
			$string_ids = $string_ids.$key.',';
		}
		else if($nr==2)
		{
			$string_ids = $string_ids.$key;
		}
		$nr++;
	}
	echo '[products ids='.$string_ids.']';
	#echo do_shortcode('[products ids="'.$string_ids.'"]');
}

add_action('admin_notices', 'getRecommendedProducts');

// We need some CSS to position the paragraph
function message_css() {
	// This makes sure that the positioning is also good for right-to-left languages
	$x = is_rtl() ? 'left' : 'right';

	echo "
	<style type='text/css'>
	#message {
		float: $x;
		padding-$x: 15px;
		padding-top: 5px;
		margin: 0;
		font-size: 11px;
	}
	</style>
	";
}

add_action( 'admin_head', 'message_css' );