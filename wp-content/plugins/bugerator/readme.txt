=== Bugerator ===
Contributors: tickerator
Donate link: http://www.tickerator.org
Tags: issue tracking, bug tracking, project tracking, submit bugs, issues, bug tracker
Requires at least: 3.4.1
Tested up to: 4.0.1
Stable tag: 1.1.7
License: GPLv2 
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple bug-tracking software easily managed on your Wordpress. Requires PHP 5.0.

== Description ==

This is a simple but complete issue tracking Wordpress plugin.  With it you can create projects and track the 
issues related to those projects.  
Features include:

* Ready to administer multiple projects right away.
* Easy install/uninstall. Uninstall is complete, nothing is left behind.
* Most output is template based so it is possible to customize the pages.
* Adds a menu option under the "Setting" menu of the dashboard.
* Display all issues, issues grouped by version and issue details.
* Can comment on issues.
* Administrator ability to edit comments & issues posted by others.
* Ability to add administrators/developers to a project without elevating their Wordpress priviledges.
* Add admins and developers by a simple drop down menu.
* Ability to administer all of the projects both from the internal Wordpress menu and the bugerator page.
* Ability to include file attachments or disallow file uploads.
* Plays well with other themes and plugins. Does not add custom Wordpress posts, uses its own tables.
* Choose to allow anonymous editing or require Wordpress usernames.
* Will automatically get rid of the WP comments section if desired.
* Automatic email sending to subscribed users.
* Most CSS options configurable in app.
* And more

== Installation ==


1. Upload `bugerator.zip` to the `/wp-content/plugins/` directory and unzip - or use the internal plugin install
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enter the short code `[bugerator]` on a blank page.
1. To limit a page to a specific project get the project ID and have the short code be `[bugerator project='1']` if your project ID is 1.


== Frequently Asked Questions ==

= Who is this for? =

This is for anybody administrating a project.

= What makes this different/better than the other open source project management systems? =

Basically it was my desire to make it so that people could have a single login for everything.  So people could just have 
a Wordpress login instead of having a separate login for the issue tracking system.

Please report bugs (or see the plugin in action) at https://tickerator.org/my_bugerator/.

== Screenshots ==

1. The initial project screen showing your choice of 3 projects (in this example)
2. The list of current issues with show hidden enabled. 
3. The version map in action.
4. The detail page of an issue.
5. A user's profile page with their options to receive email subscriptions
6. The ability to add/edit administrators without increasing their access to your blog.

== Changelog ==

= 1.1.7 =

* Couple minor bug fixes

= 1.1.6 =

* Added limits to the size of the issue map
* Now can send an email to the developer when it has been assigned
* Minor bug fixes

= 1.1.5 =

* Added network install/uninstall for wordpress multisite networks

= 1.1.4 =

* Fixed spelling & small bugs
* Added ability to reverse option corruption

= 1.1.3 =

* Fixed css disappearing bug
* Fixed first project sort bug

= 1.1.2 =

* Gee permalinks are rough.  Should work in all cases now.

= 1.1.1 =

* Fixed freak permalink bug

= 1.1.0 =

* Anonymous comments now controlled by setting
* New description field for projects
* Description tooltips on descriptions & projects
* Added custom status entries
* Now can see changes since last log in
* Renamed global variables to avoid collissions with other plugins
* Various code upgrades needed for Wordpress 3.6


= 1.0.8 =

* Updated for the newly enforced wpdb->prepare in WP 3.5
* Added customizable project sort and hiding

= 1.0.7 =

* CSS no longer overwritten by new versions
* Type now shown in list issues
* Fixed anonymous users can edit bugs
* Release date display fixed

= 1.0.6.1 =

* Fixed a little bug that killed the attachments.

= 1.0.6 = 

* Can now manage projects inside the dashboard
* Fixed issues related to ajax and FORCE_SSL_ADMIN
* Added ajaxurl definition to all pages since it isn't defined for non admins
* Fixed file upload
* Fixed export / import
* Fixed issues related to bugerator admins/devs that aren't admins for WP
* Various other fixes

= 1.0.5 = 

* I wish I could take back a release. Managed to leave a bit of debug code in the file upload so the upload was broken in 1.0.4

= 1.0.4 =

* Redid the version change. Current version = current date since that is what is released.
* Fixed the slashes in edits bug (like \' instead of ')
* Uploads file names replaced spaces with underscore
* Fixed issue subscriptions

= 1.0.3 = 

* Figured out proper Wordpress plugin header (sorry)
* Updated version map so releases show up as "Release date"
* When changing versions the previous version takes on the current date as a release date

= 1.0.2 =

* Removed "goto" for PHP 5.2 compatibility
* Fixed email issues
* Fixed Cosmetic bugs

= 1.0.1 =

* Fixed project date display bug
* Fixed readme.txt

= 1.0.0 =

* Initial release
* Has all of the features I could think of and fixed all the bugs that I could find

== Upgrade Notice ==

= 1.1.7 =

Backup bugerator.css file before updating if you customized it.  Couple bug fixes.

= 1.1.6 =

Backup bugerator.css file before updating if you customized it.  Couple display bug fixes.  Added option to email developer when you assign an issue.

= 1.1.5 =

Backup bugerator.css file before updating if you customized it.  Added network install/uninstall for wordpress multisite networks

= 1.1.4 =

Backup bugerator.css file before updating if you customized it.  Couple more new install bugs that needed to be fixed.

= 1.1.3 =

Couple large bugs that especially affected new installs.  WARNING - this will override bugerator.css so back it up if you made changes.  If you can't see your brand new project from the last version do this in order: 1) UPGRADE, 2) UNINSTALL, 3) REINSTALL.  Then it will solve the problems. Sorry the old project will be gone (which you couldn't see) but new projects will work.

= 1.1.2 =

Fixed permalink bug. You need to update.

= 1.1.1 =

Updated for Wordpress 3.6.  Bunch of new features.  Fixed a bunch of bugs.

= 1.1.0 =

Updated for Wordpress 3.6.  Bunch of new features.

= 1.0.8 = 

Updated for Wordpress 3.5 so you should probably update.  Also has a project sort in the admin screen.

= 1.0.7 =

There was a rather big bug that allowed anonymous users to edit projects so everybody should upgrade for this 
if nothing else.  Also css is no longer overwritten so if you've customized it then your changes will still be there. 
To make sure css updates check the "reset to default css" option in the admin menu.

= 1.0.6.1 =

From 1.0.6 - found a quick typo bug that killed the file upload so this fixes it.

= 1.0.6 =

I would upgrade if I were you. lol. Various bug fixes related to users who aren't admins. Enough fixes that everybody should update.

= 1.0.5 =

Bug fixes - don't use 1.0.4. I left debug code in the file upload. Sorry.

= 1.0.3 =

Bug fixes

= 1.0.2 =

Bug fixes

= 1.0.1 =

Display bug fix. 

= 1.0.0 =
Initial release.