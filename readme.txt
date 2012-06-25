=== P2 Check In ===
Contributors: ryanimel
Tags: p2, checkin, collaboration
Requires at least: 3.0
Tested up to: 3.4
Stable tag: 0.2.1
License: GPLv2 or later

This plugin adds the ability for users to "check in" to the P2 theme when they're active.

== Description ==

This plugin adds the ability for users to "check in" to the P2 theme when they're active. Once activated you'll find a new "Who is Checked In" widget that you can add to your sidebar, and a "Log In/I'm here!/I'm leaving!" button will automatically be added to your P2 theme's header.

The sidebar will list the users who are currently checked in, along with how long they have been checked in. By default users will be bumped to the bottom of the list and be shown as checked out for 30 minutes after they have left.

The language in this plugin is purposefully left a big vague so you can use it for your own purposes. Checking in could mean literally checking into a building to show you're there (the purpose I developed the plugin for) or it could mean that you are simply available, and actively watching the P2.

### Kudos

Props to the Who's Online plugin for P2, since I cribbed a number of the elements from that plugin to make the development of mine go a bit faster.

Also, the button styles are based on the Twitter Bootstrap button's styles.

== Installation ==

Upload the P2 Check In plugin to your blog, Activate it, then drop the P2 Check In widget into the sidebar where you want it to appear.

== Screenshots ==

1. The "Log In" button that displays before you've signed into the site.
2. The "I'm here!" button that will check you in. Also notice the recently checked out user faded out in the sidebar.
3. Once you are checked in the "I'm leaving!" button displays and will check you out.

== Changelog ==

= 0.2.1 =
* Added kudos to the end of the description.
* Tweaked the short description a little bit.

= 0.2 =
* Cleared out the extraneous code leftover from the fork from Who's Online.
* Added some code comments that I slacked on the first time.
* Moved the check in button logic/markup into a function that outputs into the widget itself, rather than muddying up the widget code.
* Refreshed the CSS entirely.
* Separated the list of users into two groups: checked in and checked out. This way the two won't get mixed up and out of order.
* Added a log in button, in place of the check in/out button, just in case the person isn't logged in yet.
* Fixed bug where opacity would mess up the alignment.

= 0.1 =
* First working version, implemented on a P2 blog I'm active on.
