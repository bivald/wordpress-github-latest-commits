<?php
/*
* Plugin Name: Latest Github Commits
* Plugin URI: https://github.com/bivald/wordpress-github-latest-commits
* Description: Display your recent github commits
* Version: 0.2
* Author: Niklas Bivald, Jacob Lowe
* Author URI: http://www.bivald.com/ http://www.redeyeoperations.com
*
* Inspired by ErisDS's Simple Widget Plugin (http://erisds.co.uk) 
* and Curt Zieglers Custom Recent Posts (http://www.curtziegler.com/)
*
* History
* 0.2 2011-08-29: Updated by Jacob Lowe, www.redeyeoperations.com
* 0.1 2010-11-01: Original code can be found on https://github.com/bivald/wordpress-plugins-and-themes/blob/master/plugins/latest-github-commits.php
*/

/*
	Copyright 2010-current  Niklas Bivald  (niklas@bivald.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Stop direct call to this file
if ( preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF']) ) 
{ 
	die("This file is to be included in WordPress, not linked directly.");
}

add_action( 'widgets_init', 'latest_commits_load_widgets' );

function latest_commits_load_widgets() {
	
	register_widget( 'Latest_Github_Commits' );

}

/* Widget class: Settings, form, display, and update. */
class Latest_Github_Commits extends WP_Widget {

	function Latest_Github_Commits() {
		
		$widget_ops = array(
			'classname' => 'latest_commits',
			'description' => __('An custom widget that displays your latest github commits for a project.'),
		);
		$control_ops = array(
			'width' => 200,
			'height' => 350,
			'id_base' => 'latest_commits-widget'
		);
		$name = __( 'A Simple Widget', 'simple-widget-plugin' );

		$this->WP_Widget( 'latest_commits-widget', __('Latest Github Commits', 'latest_commits'), __(5, 'cache'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		
		extract( $args );

		$title = apply_filters('widget_title', $instance['title'] );
		$max = $instance['max'];
		$cache_for = $instance['cache'];
		
		if( !is_numeric($cache_for) or $cache_for < 1 ) {
			$cache_for = 5;
		}
		
		echo $before_widget;
		
		$success = true;
		
		if (false === ($results = get_transient('latest_github_commits_json'))) {
			$ch = curl_init();

			$url = 
'http://github.com/api/v2/json/commits/list/'.urlencode($instance['github_user']).'/'.urlencode($instance['github_project']).'/master';

			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
			curl_setopt($ch, CURLOPT_URL, $url);

			$data = curl_exec($ch);
			
			if(curl_error($ch) !== '') { /* We have an curl error */
				$success = false;
			}
			
			curl_close($ch);

			$results = json_decode($data, true);
			
			if(count($results) > 0)
			
				// Changed First Parameter to Limit Commits 
			
				$results = array_slice($results['commits'], 0, $max );

			// Save a transient to the database
			set_transient('latest_github_commits_json', $results, $cache_for*60);
			

			
		}
		
		
		
		if($success == true && !isset($results['error'])) {
			
			// Changes from $results['commits'] to commits due to line 117 change
			
			foreach($results as $commit) {
				$sidebar_post_count++;
				if ($sidebar_post_count & 1) { $sidebar_post_class = " class='highlight' "; }else{ $sidebar_post_class = ""; }
				$sidebar_posts .= "<li>";
				$sidebar_posts .= "<a href='http://www.github.com/". $commit['url'] ."' ".$sidebar_post_class.">";
				$sidebar_posts .= "<span>" . date('Y-m-d H:i',strtotime($commit['committed_date'])) . " by " . 
$commit['author']['name'] . "</span><br/>";
				$sidebar_posts .= "<i>".$commit['message']."</i>";
				$sidebar_posts .= "</a>";
				$sidebar_posts .= "</li>";
			}
		}

		if(isset($results['error']))
		{
			echo "<div class='pod'>";
			echo "<h6>".$title."</h6>";
			echo "<p>Error fetching from Github: <strong>".$results['error']."</strong>. This error is cached so not to hammer Github (but 
when you update the widget settings cache is cleared)</p>";
			echo "</div>";
		}

		if(!empty($sidebar_posts))
		{
			echo "<div class='pod'>";
			echo "<h6>".$title."</h6>";
			echo "<ul class='clean'>" . $sidebar_posts . "</ul>";
			echo "</div>";
		}
			
		echo $after_widget;
	}

	/* Update settings. */
	function update( $new_instance, $old_instance ) {
		
		$instance = $old_instance;
		$instance['title'] 			= strip_tags( $new_instance['title'] );
		$instance['github_user'] 	= strip_tags( $new_instance['github_user'] );
		$instance['github_project'] = strip_tags( $new_instance['github_project'] );
		

		if(is_numeric($new_instance['max']))
			$instance['max'] = $new_instance['max'];

		if(is_numeric($new_instance['cache']))
			$instance['cache'] = $new_instance['cache'];

		// Remove our saved cache (in case we changed cache/max values for example) 
		delete_transient('latest_github_commits_json');
		
		return $instance;
	}

	function form( $instance ) {
		
		
		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('Latest Github Commits', 'latest_commits'), 'Github User' => __('Github User', 'github_user'), 
'Github Project' => __('Github Project', 'github_project'), 'Max commits' => 5);
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php 
echo $instance['title']; ?>" style="width:95%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'github_user' ); ?>"><?php _e('Github User:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'github_user' ); ?>" name="<?php echo $this->get_field_name( 'github_user' ); ?>" 
value="<?php echo $instance['github_user']; ?>" style="width:95%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'github_project' ); ?>"><?php _e('Github Project:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'github_project' ); ?>" name="<?php echo $this->get_field_name( 'github_project' ); 
?>" value="<?php echo $instance['github_project']; ?>" style="width:95%;" />
		</p>


		<p>
			<label for="<?php echo $this->get_field_id( 'max' ); ?>"><?php _e('Max:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'max' ); ?>" name="<?php echo $this->get_field_name( 'max' ); ?>" value="<?php echo 
$instance['max']; ?>" style="width:100%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'cache' ); ?>"><?php _e('Cache For X minutes:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'cache' ); ?>" name="<?php echo $this->get_field_name( 'cache' ); ?>" value="<?php 
echo $instance['cache']; ?>" style="width:100%;" />
		</p>

	<?php
	}
}
?>
