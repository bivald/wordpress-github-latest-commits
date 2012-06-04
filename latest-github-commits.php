<?php

/*
* Plugin Name: Latest Github Commits
* Plugin URI: http://www.github.com/bivald/latest-github-commits
* Description: Display your recent github commits
* Version: 0.3
* Author: Niklas Bivald
* Author URI: http://www.bivald.com/
* Modified by:
* 	 Ben Keen, @vancouverben
*    http://www.benjaminkeen.com
*    June 2012
*    Updates: - option to specify all repositories or comma-delimited list
*             - option to only return your own commits, not other people's
*
* Inspired by ErisDS's Simple Widget Plugin (http://erisds.co.uk)
* and Curt Zieglers Custom Recent Posts (http://www.curtziegler.com/)
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
wp_enqueue_script('myscript', '/wp-content/plugins/latest-github-commits/widget.js');


function latest_commits_load_widgets() {
  register_widget( 'Latest_Github_Commits' );
}

/* Widget class: Settings, form, display, and update. */
class Latest_Github_Commits extends WP_Widget {

  /**
   * For debugging / dev purposes. This disables the cache so it's always polling github's API.
   * @var boolean
   */
  private static $disableCache = false;

  /**
   * The default number of minutes to cache.
   * @var integer
   */
  private static $defaultCacheMins = 30;



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
    $name = __('A Simple Widget', 'simple-widget-plugin');

    $this->WP_Widget('latest_commits-widget', __('Latest Github Commits', 'latest_commits'), __(5, 'cache'), $widget_ops, $control_ops);
  }


  function widget($args, $instance)
  {
    extract($args);

    $title = apply_filters('widget_title', $instance['title'] );
    $max = $instance['max'];
    $cache_for = $instance['cache'];

    if (!is_numeric($cache_for) or $cache_for < 1)
    {
      $cache_for = self::$defaultCacheMins;
    }

    // some Wordpress magic, I guess
    echo $before_widget;

    $success = true;
    $commits = array();
    if (false === ($commits = get_transient('latest_github_commits_php')))
    {
        // get the list of all repositories, or specific ones
    	if ($instance["github_project_choice"] == "all")
      {
        $repository_names = self::getUserRepositories($instance['github_user']);
      }
      else
      {
        $repository_names = explode(",", $instance["github_project"]);
      }

      if ($repository_names !== false)
      {
        // loop through all repositories and get the latest N ($max) commits
        $commits = self::getLatestCommits($instance['github_user'], $instance["github_commit_users"], $repository_names, $max);
      }

      // Save a transient to the database
      if (!self::$disableCache && $success)
      {
      	set_transient('latest_github_commits_php', $commits, $cache_for * 60);
      }
    }

    $sidebar_posts = "";
    if ($success == true)
    {
      foreach ($commits as $commit)
      {
      	// only include the "by" text if there's more than one committer to be listed
      	$by = "";
      	if ($instance["github_commit_users"] == "all")
      	{
          $by = "by " . $commit['author']['name'];
      	}

      	$repo_url = preg_replace("/[^\/]*$/", "", $commit['url']);
      	$repo_url = preg_replace("/commit\/$/", "", $repo_url);

        $sidebar_posts .= "<li>";
        $sidebar_posts .= "<a href=\"$repo_url\" class=\"latest-github-commits-repository-name\">{$commit["repo_name"]}</a> &#8212 ";
        $sidebar_posts .= "<span>" . date($instance["date_format"], $commit['committed_date']) . $by . "</span><br/>";
        $sidebar_posts .= "<a href=\"http://www.github.com{$commit['url']}\" class=\"latest-github-commits-commit-link\">{$commit['message']}</a>";
        $sidebar_posts .= "</li>";
      }
    }

    if (isset($results['error']))
    {
      echo "<div class=\"pod\">";
      echo "<h4 class=\"widgettitle\">$title</h4>";
      echo "<p>Error fetching from Github: <strong>".$results['error']."</strong>.";
      echo "</div>";
    }

    if (!empty($sidebar_posts))
    {
      echo "<div class=\"pod\">";
      echo "<h4 class=\"widgettitle\">$title</h4>";
      echo "<ul class='clean'>" . $sidebar_posts . "</ul>";
      echo "</div>";
    }

    // more Wordpress magic
    echo $after_widget;
  }


  /**
   * Returns an array of the user's repositories (names only). Used when the user checks "All
   * repositories" in the widget, to let the plugin determine the appropriate github API query
   *
   *
   * @return mixed array an array of repository info or false, if there was a CURL problem.
   */
  static function getUserRepositories($username)
  {
    $username = urlencode($username);

    $ch = curl_init();
    $url = "http://github.com/api/v2/json/repos/show/$username";

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);

    if (curl_error($ch) !== '')
    {
      return false;
    }
    curl_close($ch);

    $results = json_decode($data, true);

    $repository_names = array();
    foreach ($results as $repositories)
    {
      foreach ($repositories as $repo_info)
      {
        $repository_names[] = $repo_info["name"];
      }
    }

    return $repository_names;
  }


  /**
   * This is potentially quite slow. If the user chooses to pull from ALL repositories, a separate CURL request
   * is performed for each.
   *
   * @param string $username
   * @param array $repository_names
   * @param string $commit_source "all" or "user_only"
   * @param number $max
   */
  static function getLatestCommits($username, $commit_source, $repository_names, $max)
  {
    $clean_username = urlencode($username);

    // get a big ol' list of all commits
    $all_commits = array();
    foreach ($repository_names as $repo_name)
    {
      $clean_repo_name = urlencode($repo_name);
      $ch = curl_init();
      $url = "http://github.com/api/v2/json/commits/list/$clean_username/$clean_repo_name/master";

      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
      curl_setopt($ch, CURLOPT_URL, $url);
      $data = curl_exec($ch);

      if (curl_error($ch) !== '')
      {
        return false;
      }
      curl_close($ch);

      $repo_commits = json_decode($data, true);

      $repo_commits["repo_name"] = $repo_name;
      $all_commits[] = $repo_commits;
    }

    // now sort them by date and return the $max most recent commit details
    $sorted_commits = array();
    foreach ($all_commits as $curr_group)
    {
    	$repository_name = $curr_group["repo_name"];
      $commits         = $curr_group["commits"];

      if (!is_array($commits))
        continue;

      foreach ($commits as $commit_info)
      {
      	// urgh!! date is in the following format: 2012-05-05T12:23:15-07:00....
        list($date, $time) = explode("T", $commit_info["committed_date"]);
        list($year, $month, $day) = explode("_", $date);
        list($hours, $mins, $secs_and_offset) = explode(":", $time);
        $secs = substr($secs_and_offset, 0, 2);
        $offset = (int) substr($secs_and_offset, 2);
        $offset_secs = 60 * 60 * $offset;

	      $committed_unixtime = strtotime($commit_info["committed_date"]) + $offset_secs;
	      $commit_info["committed_date"] = $committed_unixtime;

	      if ($commit_source == "only_user" && ($commit_info["author"]["login"] != $username))
	      {
	        continue;
	      }

	      $commit_info["repo_name"] = $repository_name;
	      $sorted_commits[$committed_unixtime] = $commit_info;
      }
    }

    ksort($sorted_commits, SORT_NUMERIC);
    $sorted_commits = array_reverse($sorted_commits);
    $sorted_commits = $sorted_commits;
    $latest_commits = array_slice($sorted_commits, 0, $max);

    return $latest_commits;
  }


  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] 			= strip_tags($new_instance['title']);
    $instance['github_user'] 	= strip_tags($new_instance['github_user']);
    $instance['github_project'] = strip_tags($new_instance['github_project']);
    $instance['github_project_choice'] = strip_tags($new_instance['github_project_choice']);
    $instance['github_commit_users'] = strip_tags($new_instance['github_commit_users']);
    $instance['date_format'] = strip_tags($new_instance['date_format']);

    if (is_numeric($new_instance['max']))
    {
      $instance['max'] = $new_instance['max'];
    }

    if (is_numeric($new_instance['cache']))
    {
      $instance['cache'] = $new_instance['cache'];
    }

    // Remove our saved cache (in case we changed cache/max values for example)
    delete_transient('latest_github_commits_php');

    return $instance;
  }


  function form($instance)
  {
    // Set up some default widget settings.
    $defaults = array(
      'title'                 => __('Latest Github Commits', 'latest_commits'),
      'Github User'           => __('Github User', 'github_user'),
      'Github Project'        => __('Github Project', 'github_project'),
      'github_commit_users'   => "only_user",
      'github_project_choice' => "all",
      'max'                   => 5,
      'date_format'           => 'M jS, g:i A'
    );

    $instance = wp_parse_args( (array) $instance, $defaults ); ?>

    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'hybrid'); ?></label>
      <input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width:95%;" />
    </p>

    <p>
      <label for="<?php echo $this->get_field_id('github_user'); ?>"><?php _e('Github User:', 'hybrid'); ?></label>
      <input id="<?php echo $this->get_field_id('github_user'); ?>" name="<?php echo $this->get_field_name('github_user'); ?>"
        value="<?php echo $instance['github_user']; ?>" style="width:95%;" />
    </p>

    <p>
    <label for="<?php echo $this->get_field_id('github_project'); ?>"><?php _e('Github Project(s):', 'hybrid'); ?></label>
    <select name="<?php echo $this->get_field_name('github_project_choice'); ?>" style="width: 95%" class="widget-latest-github-commits-project-choice">
	    <option value="all" <?php if ($instance['github_project_choice'] == "all") echo "selected"; ?>><?php _e('All projects', 'hybrid'); ?></option>
		  <option value="specific" <?php if ($instance['github_project_choice'] == "specific") echo "selected"; ?>><?php _e('Specific projects:', 'hybrid'); ?></option>
		</select>
		<span class="widget-latest-github-commits-project-choice-specific" <?php if ($instance['github_project_choice'] == "all") echo "style=\"display: none\""; ?>>
      <input id="<?php echo $this->get_field_id('github_project'); ?>" name="<?php echo $this->get_field_name('github_project'); ?>"
        value="<?php echo $instance['github_project']; ?>" style="width:95%;" />
        (comma-delimited)
    </span>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'github_commit_users' ); ?>"><?php _e('Commits to return:', 'hybrid'); ?></label><br />
	    <select name="<?php echo $this->get_field_name('github_commit_users'); ?>" style="width: 95%" class="widget-latest-github-commits-project-choice">
		    <option value="all" <?php if ($instance['github_commit_users'] == "all") echo "selected"; ?>><?php _e('All users', 'hybrid'); ?></option>
			  <option value="only_user" <?php if ($instance['github_commit_users'] == "only_user") echo "selected"; ?>><?php _e('Only commits by user', 'hybrid'); ?></option>
			</select>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'max' ); ?>"><?php _e('Max:', 'hybrid'); ?></label>
      <input id="<?php echo $this->get_field_id( 'max' ); ?>" name="<?php echo $this->get_field_name( 'max' ); ?>" value="<?php echo $instance['max']; ?>" style="width:30px" />
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'cache' ); ?>"><?php _e('Cache For X minutes:', 'hybrid'); ?></label>
      <input id="<?php echo $this->get_field_id( 'cache' ); ?>" name="<?php echo $this->get_field_name( 'cache' ); ?>" value="<?php echo $instance['cache']; ?>" style="width:30px" />
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'date_format' ); ?>"><?php _e('Date format:', 'hybrid'); ?></label>
      <input id="<?php echo $this->get_field_id( 'date_format' ); ?>" name="<?php echo $this->get_field_name( 'date_format' ); ?>" value="<?php echo $instance['date_format']; ?>" style="width:100px" /><br />
      &#8212; see <a href="http://php.net/manual/en/function.date.php" target="_blank">PHP date()</a>
    </p>

  <?php
  }
}
?>