=== QQWorld Auto Save Images ===
Contributors: Michael Wang
Tags: auto, save, local, fetch, images
Requires at least: 3.0
Tested up to: 4.0
Stable tag: 1.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically keep the all remote picture to the local media libary when you publishing posts, and automatically set featured image.

== Description ==

Automatically keep the all remote picture to the local media libary when you publishing posts, and automatically set featured image.

And more powerful functional is waiting for you. What exactly is it? Hehe...

<h4>Notice:</h4>

This plugin has a little problem that is all the image url must be full url, it means must include "http(s)://", for example:

<ul>
<li>&lt;img src=&quot;http://img.whitehouse.gov/image/2014/08/09/gogogo.jpg&quot; /&gt;</li>
<li>&lt;img src=&quot;http://www.bubugao.me/image/travel/beijing.png?date=20140218&quot; /&gt;</li>
<li>&lt;img src=&quot;http://r4.ykimg.com/05410408543927D66A0B4D03A98AED24&quot; /&gt;</li>
<li>&lt;img src=&quot;http://example.com/image?id=127457&quot; /&gt;</li>
</ul>

The examples that not working:

<ul>
<li>&lt;img src=&quot;/images/great.png&quot; /&gt;</li>
<li>&lt;img src=&quot;./photo-lab/2014-08-09.jpg&quot; /&gt;</li>
<li>&lt;img src=&quot;img/background/black.gif&quot; /&gt;</li>
</ul>

I have tried to figure this out, but i couldn't get the host name to make image src full, nor get remote image from dynamic link.

So if you encounter these codes, plaese manually fix the images src to full url.

////////////////////////////////////////////////////

在发布文章时自动保存远程图片到本地媒体库，自动设置特色图片，并且支持机器人采集软件从外部提交。

还有更强大的功能等着你，到底是什么呢？呵呵……

<h4>注意：</h4>

该插件有个小问题，所有的远程图像的URL必须是完整的，就是说必须得包含"http(s)://"，比如：

<ul>
<li>&lt;img src=&quot;http://img.whitehouse.gov/image/2014/08/09/gogogo.jpg&quot; /&gt;</li>
<li>&lt;img src=&quot;http://www.bubugao.me/image/travel/beijing.png?date=20140218&quot; /&gt;</li>
<li>&lt;img src=&quot;http://r4.ykimg.com/05410408543927D66A0B4D03A98AED24&quot; /&gt;</li>
<li>&lt;img src=&quot;http://example.com/image?id=127457&quot; /&gt;</li>
</ul>

不能保存的例子：

<ul>
<li>&lt;img src=&quot;/images/great.png&quot; /&gt;</li>
<li>&lt;img src=&quot;./photo-lab/2014-08-09.jpg&quot; /&gt;</li>
<li>&lt;img src=&quot;img/background/black.gif&quot; /&gt;</li>
</ul>

我尝试解决这个问题，但是我无法让程序获取到主机名从而让图片的URL完整或是从动态链接获取图像。

所以，如果你碰到这样的代码，请手动将图片地址改成完整的，或者使用采集工具自动补完图片的URL然后从外部提交给Wordpress。

== Installation ==

<ol>
<li>Make sure the server configuration <strong>allow_url_fopen=1</strong> in php.ini.<br />
请确保php.ini的设置中 <strong>allow_url_fopen=1</strong></li>

<li>Warning: If your website domain has been changed, you must modify all image link to new domain from database, or else all images which not modified in post content will be save again.<br />
警告：如果你的网站域名更改了，必须在数据库中将所有的图片链接更新到新域名，否则插件会把未更改的图片再保存一次。</li>
</ol>

== Screenshots ==

1. User interface
2. 用户界面

== Changelog ==

= 1.7 =
<ul>
	<li>New features:
		<ol>
			<li>New interface.</li>
			<li>Added categories filter for scan-post-mode.</li>
		</ol>
	</li>
	<li>新特性：
		<ol>
			<li>新用户界面。</li>
			<li>扫描文章模式增加分类筛选。</li>
		</ol>
	</li>
</ul>

= 1.6.1 =
<ul>
	<li>New features:
		<ol>
			<li>Supported more type of dynamic link.</li>
			<li>More detail notifications for automatic/manual mode.</li>
		</ol>
	</li>
	<li>新特性：
		<ol>
			<li>支持更多类型的动态连接。</li>
			<li>自动/手动模式拥有更详细的通知信息</li>
		</ol>
	</li>
</ul>

= 1.6 =
New feature: Supported fetching images from some dynamic link.<br />
新特性：支持从部分动态连接抓取图片。

= 1.5.9.1 =
fixed a bug of regular expression.<br />
修复正则表达式的bug。

= 1.5.9 =
New feature: Set Exclude-Domain/Keyword.<br />
新特性：设置排除域名/关键词。

= 1.5.8 =
New feature: supported XMLRPC, means remote publishing from IFTTT.<br />
新特性：支持XMLRPC，意味着支持从IFTTT远程发布。

= 1.5.7.5 =
fixed a bug of regular expression.<br />
修复正则表达式的bug。

= 1.5.7.4 =
Hide posts do not have remote images from list in automatic mode.<br />
在自动扫描的列表中隐藏没有远程图像的文章。

= 1.5.7.3 =
Show post number in error notification<br />
在错误提示中显示文章的序号

= 1.5.7.2 =
The debug system can pass errors let process go to the end<br />
增加调试系统可以跳过错误让进程运行到底

= 1.5.7.1 =
Debug system added<br />
增加调试系统

= 1.5.7 =
<ul>
	<li>Fixed:
		<ol>
			<li>A bug of the stops process by some special characters.</li>
			<li>some english error messages.</li>
		</ol>
	</li>
	<li>修复:
		<ol>
			<li>一些特殊字符停止程序进程的bug。</li>
			<li>一些英文错误消息。</li>
		</ol>
	</li>
</ul>

= 1.5.6 =
<ul>
	<li>New features:
		<ol>
			<li>Set scope of ID for scan.</li>
			<li>List posts including remote images and handle them manually.</li>
		</ol>
	</li>
	<li>新功能：
		<ol>
			<li>设置文章ID的扫描范围。</li>
			<li>列出包含远程图像的文章，并且手动处理它们。</li>
		</ol>
	</li>
</ul>

= 1.5.5 =
Enhance the scanning speed of up to 10 times.<br />
可提升最多10倍扫描速度

= 1.5.4 =
Because of PHP runs for a limited time, so now using ajax to scan posts. but it runs slower than 1.5.3.<br />
因为PHP有运行时间限制，所以现在改为使用AJAX来扫描文章。但运行速度比1.5.3慢。

= 1.5.3 =
Added a filter set scan range for scanner<br />
添加过滤器来设置扫描仪的扫描范围

= 1.5.2 =
Make the plugin more proffesional<br />
让插件更专业

= 1.5.1 =
Make the plugin more secure<br />
让插件更安全

= 1.5 =
New feature: Scan posts and save remote images in all posts to local media library. Maybe take a long time.<br />
新功能：扫描所有的文章，将所有远程图片保存到本地，可能需要很长时间。

= 1.4.3 =
Now you can choose automatically set featured image or not.<br />
现在你可以选择是否自动设置特色图片。

= 1.4.2 =
Fixed 1 bug: If remote images are too big or too many, program need more time to download, When default time out, whole process stoped.<br />
If remote images are too many, sometimes you need fetch twice.<br />
修复bug：如果远程图片太大或太多，程序需要更多时间来下载，当默认的运行超时，程序会卡住。<br />
如果远程图片太多，有时候需要抓取两次。

= 1.4.1 =
New functional optimized<br />
优化了新功能

= 1.4 =
Add a new type allow user manually save remote images via click a button on the top of editor.<br />
You can change the Type in the plugin's setting page.<br />
添加一个新类型允许用户通过单击编辑器顶部的按钮手动保存远程图像。<br />
你可以在插件的设置页面更改类型。

= 1.3 =
<ul>
	<li>Fixed 2 bugs:
		<ol>
			<li>Failed to save remote image to local when the image url include "(\?(.*?))?", now it's works.</li>
			<li>If the remote image url is invalid, will not to save it to a blank attachment.</li>
		</ol>
	</li>
	<li>修复两个bug:
		<ol>
			<li>如果远程图片地址包含 "(\?(.*?))?" 会无法保存到本地，现在工作正常。</li>
			<li>如果远程图片地址无效，则不会保存一个空的附件到本地。</li>
		</ol>
	</li>
</ul>

= 1.2 =
add admin page to control use which action to save remote image.<br />
添加后台页面来管理选择使用下载远程图片的动作。

= 1.1 =
Remove auto rename.<br />
移除自动更名功能。<br />
if you want to upload images with chinese file name and server does not support chinese filename, please using 1.0.<br />
如果需要上传中文名图片，服务器又不支持中文文件名，请使用1.0

= 1.0 =
Born for Capture.<br />
The feature is can automatically change image filename.<br />
为采集而生<br />
特色是可以自动更改图片的文件名

== Upgrade Notice ==

= 1.1 =
if you want to upload images with chinese file name and server does not support chinese filename, please using 1.0.<br />
如果需要上传中文名图片，服务器又不支持中文文件名，请使用1.0

= 1.0 =
The feature is can automatically change image filename.<br />
特色是可以自动更改图片的文件名