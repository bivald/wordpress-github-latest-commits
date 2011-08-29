=== Plugin Name ===
Contributors: bivald
Donate link: http://www.bivald.com/wordpress/
Tags: github, latest, commits
Requires at least: 3.0.1
Tested up to: 3.0.1
Stable tag: trunk


Latest Github Commits is a simple WordPress widget to fetch your latest commits from Github using their API.

== Description ==

Latest Github Commits is a simple WordPress widget to fetch your latest commits from Github using their API.

You need to configure:
* Github owner of the project (i.e bivald in my case)
* Project URI (i.e wordpress-latest-github-commits in my case)

You can also set
* Widget title
* Max commits to list
* How long to cache the results

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `latest-github-commits.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add the plugin to your sidebar as a widget

== Frequently Asked Questions ==

= How does it cache the results? =

It uses Wordpress transient storage to cache the results. 

= Is it stable? =

So far, it appears to be. I use it on www.munin-mobile.com, of course the GitHub API can change without notice. 
Consider it a beta. 

== Changelog ==

= 0.1 =

Initial relase
