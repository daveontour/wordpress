=== Quay Risk Manager ===
Contributors: David Burton,
Tags: risk
Requires at least: 3.8
Tested up to: 4.3
Stable tag: 1.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage your organisations's portfolio of risks. 

== Description ==
Quay Risk Manager allows you to have complete visibility and control of your organisation's risk portfolio.

QRM gives you the tools to make informed decisions in relation to your risk portfolio and to support your risk governance processes

1. *Identify* Quay Risk Manager provided a common repository for your organisation's risks. The repository allows risks to be organised hierarchically based on any structure you decide. A library of templates repositories and a library of common risks are available from Quay Systems which can be imported into your repository.
2. *Analyse* QRM provides a number of tools to help you analyse individual risk and the entire portfolio of risks.
3. *Manage* QRM assigns risks to Risk Owners and Risk Managers and provide milestone audit points along the lifcycle of a risk. Incident or risk occurence can be recorded and analysedas well as scheduling audits and reviews of sets of risks. 
4. *Collaborate* QRM integrate into your WordPress site. Site administrators can select which WordPress users can access Quay Risk Manager via your site's menu structure
5. *Report* QRM produces reports in PDF using a web service. Quay Systems can customise reports to your specific needs. 


== Installation ==
1. Upload "qrm.zip" via WordPres's Plugins->Add New menu.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to the QRM Setting page either by selecting "Settings" on the Quay Risk Manager entry of the plugins list or "QRM Settings" Menu.
4. Before you can create your first risk project, select the users who can have access to Quay Risk Manager.
5. Select "Install Sample Data" in the "QRM Settings" page or create your first Risk Project via the menu Risk Projects->Add Risk Project.
6. Choose how you want to give users access to Quay Risk Manager by configuring the "Quay Risk Manager" page into one of your site's menus.
7. QRM Risk Admistrators can access QRM via "View" on any of the QRM post types.   

== Frequently Asked Questions ==
= Selecting the "Quay Risk Manager" page just shows the page in the default format =
 Select the page and choose "Edit". Make sure the template "Quay Risk Manager Main Page" is selected

= There is a "Add Project" menu option, but no "Add Risk" option =
 Risk can only be created from within Quay Risk Manager itself. Select a project to view or select the Quay Risk Manager page to enter QRM.

= How can I delete a risk, risk project, incident or review? =
 These items can only be deleted by QRM Risk Administrator by selecting the items for deletion in the risk items listing from the WordPress admin menu.

= How do I make a user a QRM Risk Administrator? =
 From the WordPress Users menu, select the user and give them the role "Risk Administrator"

= How do I navigate around Quay Risk Manager? =
 Select the menu icon (three bars) in the top left of QRM to display the navigation menu
 
= How are reports generated? =
 Reports are generated in PDF format by packaging the relevant data and sending the data to the Quay Systems Report Server via a web service where it is stored only long enough to generate the selected report and then deleted.
 
= Can I use a different report server? =
You can install your own QRM Report Generator on your own network. Contct Quay Systems to discuss how.  

== Screenshots ==
1. The main Risk Explorer. Use this screen to navigate the risk projects and and select the risk to view/edit
2. The Risk Editor for viewing or editing risks
3. The Risk Editor (continued)
4. The Tolerance Matrix showing the relative probability/impact for all the risks of the selected project
5. The Risk Calendar allows you to see the times which you are exposed to particular risks
6. The Analysis Tools contain multiple charts to help you understand the composition of your risk porfolio

== Changelog ==
= 1.3 =
* Intial public release
= 1.3.1 =
* Changes to make use of WordPress version of jQuery
= 1.3.2 = 
* Added "Check for Completed Reports" to the "Reports" menu on the Risk Explorer. This will download any reports that have not been downloaded for the sessions. This is primarily for unregistered sites using the Quay Systems Report Server. Registered sites will be able to see all their reports in the the "Archived Reports" menu