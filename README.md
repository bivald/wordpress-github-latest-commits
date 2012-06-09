#Github-Latest-Commits

Author: bivald - http://www.bivald.com/wordpress/

Contributors: [benkeen](https://github.com/benkeen)

Tested on Wordpress 3.3.2

## Description
Latest Github Commits is a simple WordPress widget to fetch your latest commits from Github. It allows you to choose to specify one or more of your repositories and whether or not the commits listed are purely your own - or anyone else's contributions. To prevent long page loads, results are cached in Wordpress transient storage for a period of your choosing.

You need to configure:
* Github owner of the project (i.e bivald in the author's case)
* Github repo name(s), or allow it to pull commit data from all your repos

Optional configurations:
* Widget title
* Max commits to list
* How long to cache the results (defaults to 30 mins)
* Date formatting
* Whether or not the commits to be displayed are only your own, or anyone contributing to your projects

## Installation
1. Upload `latest-github-commits.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add the plugin to somewhere on your site (sidebar, footer etc) as a widget

## Notes
* The option to let you pull from all your repositories is convenient, but can be very slow when the data is first polled if you have a large number of repos. So generally, you'll want to specify which of the repositories you're interested in.
* Right now, the format of the outputted commit data is pretty basic. I hope to finer controls at a later date.

## Changelog

_0.3_
Updated by [Ben Keen](https://github.com/benkeen) to provide a little more functionality: date formatting; option to pull from multiple repositories or all repos; option to only display commits by yourself, or all committers to your repo.

_0.2_
Patched by Jacob Lowe, [redeyeoperations.com](http://redeyeoperations.com)

_0.1_
Initial relase

Ben Keen
[@vancouverben](https://twitter.com/#!/vancouverben)