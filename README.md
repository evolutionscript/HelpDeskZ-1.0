![](/images/logo.png)

Version: 1.0.2 from 1st June 2015<br>
Developed by: Evolution Script S.A.C.<br>
[Help Desk Software HelpDeskZ](http://www.helpdeskz.com)

HelpDeskZ is a free PHP based software which allows you to manage your site's support with a web-based support ticket system.

## Requirements

HelpDeskZ requires:

- PHP 5.x
- MySQL database
- GD Library (only for captcha verification)
- Mod_rewrite (only if you want to use permalinks)-

## Upgrading

To upgrade your HelpDeskZ from previus versions, please read the file UPGRADING.txt

## Installation steps

- Connect with FTP to the <em>public folder</em> of your server where the rest of your Web site is
- Create a new folder where you will install HelpDeskZ. Name it anything you like, for example "helpdesk" or "support".<br>
Example: /public_html/support<br>
Corresponding URL: http://www.site.com/support
- Upload all HelpDeskZ files to your server.
- Open **/install** in your browser, for example (modify to your URL):<br />
http://www.site.com/support/install
- The HelpDeskZ setup script will run. Click <strong>INSTALL HELPDESKZ</strong> and follow instructions through License agreement, Check Setup and Database settings.
- Before closing the install script **DELETE the "install" directory from your server!**
- Now it's time to setup your help desk! Open the <strong>staff</strong> panel in your browser, for example:<br />
http://www.site.com/support/?v=staff<br />
Use the login details that you enter in the installation process.
- Go to <strong>Settings -&gt; General</strong> to get to the settings page.
- Take some time and get familiar with all the available settings. Most should be self-explanatory,
- If you want to use permalinks like http://www.site.com/support/staff/ then you have to enable this option on Settings -> General -> Helpdesk -> Use SEO-friendly URLs
- Good luck using HelpDeskZ!
- 
## Email Piping
HelpDeskZ supports email piping, this allows the creation of tickets from incoming emails.
- To enable email piping for your help desk follow this <a href="http://www.helpdeskz.com/help/knowledgebase/2/article/10/setting-up-email-piping">email piping tutorial.</a>

## Customize the look
For detailed information please see this <a href="http://www.helpdeskz.com/help/knowledgebase/2/article/6/how-do-i-customize-helpdeskz-look">knowledgebase article.</a>

## Translate HelpDeskZ to your language
To translate HelpDeskZ read <a href="http://www.helpdeskz.com/help/knowledgebase/2/article/8/how-can-i-translate-helpdeskz">How can I translate HelpDeskZ.</a>

## Css for mobile

![Small Device](/docs/captures/small-device.png?raw=true)

![Medium Device](/docs/captures/medium-device.png?raw=true)

![Large Device](/docs/captures/large-device.png?raw=true)