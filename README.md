#Github-Latest-Commits

Author: bivald - http://www.bivald.com/wordpress/

Contributors: bivald, benkeen

Tested on Wordpress 3.3.2


## Description

Latest Github Commits is a simple WordPress widget to fetch your latest commits from Github via their API. It allows you to choose to specify one or more repositories and whether or not the commits listed are purely your own - or anyone on your repos.

You need to configure:
* Github owner of the project (i.e bivald in the author's case)
* Github repo name(s), or allow it to pull commit data from all your repos (not a good idea if you have a lot! It will run slow!)

Optional configurations:
* Widget title
* Max commits to list
* How long to cache the results
* The date formatting option
* Whether or not the commits to be displayed are only your own, or anyone contributing to your projects

## Installation
1. Upload `latest-github-commits.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add the plugin to your sidebar as a widget

## FAQ
How does it cache the results?

It uses Wordpress transient storage to cache the results.

= Is it stable? =
Hard to say. This latest version is based an a much older version. I (Ben Keen) am sing on an upcoming site (June 2012) so if it isn't, I'll be updating it soon.

## Changelog

= 0.3 =
Updated by Ben Keen, @vancouverben to provide a little more functionality: date formatting; option to pull from multiple repositories or all repos; option to only display commits by yourself, or all committers to your repo.

= 0.2 =
Patched by Jacob Lowe, redeyeoperations.com

= 0.1 =
Initial relase


Ben Keen
[@vancouverben](https://twitter.com/#!/vancouverben)
