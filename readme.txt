=== QQWorld Auto Save Images ===
Contributors:
Donate link:
Tags: auto, save, local, fetch, images
Requires at least: 3.5
Tested up to: 4.1
Stable tag: 1.7.12.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically keep the all remote picture to the local media libary when you publishing posts, and automatically set featured image.

== Description ==

Automatically keep the all remote picture to the local media libary when you publishing posts, and automatically set featured image.

And more powerful functional is waiting for you. What exactly is it? Hehe...

<h4>Notice:</h4>
<ul>
	<li>This plugin has a little problem that is all the image url must be full url, it means must included "http(s)://", for example:
		<ul>
			<li>&lt;img src=&quot;http://img.whitehouse.gov/image/2014/08/09/gogogo.jpg&quot; /&gt;</li>
			<li>&lt;img src=&quot;http://www.bubugao.me/image/travel/beijing.png?date=20140218&quot; /&gt;</li>
			<li>&lt;img src=&quot;http://r4.ykimg.com/05410408543927D66A0B4D03A98AED24&quot; /&gt;</li>
			<li>&lt;img src=&quot;https://example.com/image?id=127457&quot; /&gt;</li>
		</ul>
	</li>
	<li>The examples that not works:
		<ul>
			<li>&lt;img src=&quot;/images/great.png&quot; /&gt;</li>
			<li>&lt;img src=&quot;./photo-lab/2014-08-09.jpg&quot; /&gt;</li>
			<li>&lt;img src=&quot;img/background/black.gif&quot; /&gt;</li>
		</ul>
	</li>
</ul>
I'v tried to figure this out, but i couldn't get the host name to make image src full.

So if you encounter these codes, plaese manually fix the images src to full url.

////////////////////////////////////////////////////

在发布文章时自动保存远程图片到本地媒体库，自动设置特色图片，并且支持机器人采集软件从外部提交。

还有更强大的功能等着你，到底是什么呢？呵呵……

<h4>注意：</h4>
<ul>
	<li>该插件有个小问题，所有的远程图像的URL必须是完整的，就是说必须得包含"http(s)://"，比如：
		<ul>
			<li>&lt;img src=&quot;http://img.whitehouse.gov/image/2014/08/09/gogogo.jpg&quot; /&gt;</li>
			<li>&lt;img src=&quot;http://www.bubugao.me/image/travel/beijing.png?date=20140218&quot; /&gt;</li>
			<li>&lt;img src=&quot;http://r4.ykimg.com/05410408543927D66A0B4D03A98AED24&quot; /&gt;</li>
			<li>&lt;img src=&quot;https://example.com/image?id=127457&quot; /&gt;</li>
		</ul>
	</li>
	<li>不能保存的例子：
		<ul>
			<li>&lt;img src=&quot;/images/great.png&quot; /&gt;</li>
			<li>&lt;img src=&quot;./photo-lab/2014-08-09.jpg&quot; /&gt;</li>
			<li>&lt;img src=&quot;img/background/black.gif&quot; /&gt;</li>
		</ul>
	</li>
</ul>
我尝试解决这个问题，但是我无法让程序获取到主机名从而让图片的URL完整。

所以，如果你碰到这样的代码，请手动将图片地址改成完整的，或者使用采集工具自动补完图片的URL然后从外部提交给Wordpress。

== Installation ==

<ol>
<li>Make sure the server configuration <strong>allow_url_fopen=1</strong> and remove ";" before <strong>extension=php_gd2.dll</strong> in php.ini.<br />
请确保php.ini的设置中 <strong>allow_url_fopen=1</strong>，并且去掉<strong>extension=php_gd2.dll</strong>之前的“;”</li>
<li>Warning: If your website domain has been changed, you must modify all image link to new domain from database, or else all images which not modified in post content will be save again.<br />
警告：如果你的网站域名更改了，必须在数据库中将所有的图片链接更新到新域名，否则插件会把未更改的图片再保存一次。</li>
</ol>

== Screenshots ==

1. User interface - Settings
2. 用户界面 - 设置
3. User interface - Preview watermark feature
4. 用户界面 - 水印功能预览
5. User interface - Scan posts
6. 用户界面 - 扫描文章

== Changelog ==

= 1.7.12.3 =
New feature:<br />
Use curl instead of fopen to download remote images.<br />
新特性：<br />
使用cUrl代替fopen来下载远程图片

= 1.7.12.2 =
Bug fixed:<br />
Now the option 3 of change name feature would not generate URL encoded file name.<br />
修复Bug：<br />
现在更改文件名功能的第3项不会产生URL编码的的文件名了。

= 1.7.12.1 =
Bug fixed:<br />
Now the feature automatic-change-remote-images-filename-and-alt-as-post-name supporte IFTTT.<br />
修复Bug：<br />
现在自动更改文件名为文章名(Post Name | Slug)已支持IFTTT.

= 1.7.12 =
Mew format feature:<br />
Automatic change remote images filename and alt as post name. if you choose this, please make sure post name | slug exclude Chinese or other East Asian characters.<br />
格式化新功能：<br />
自动更改文件名为文章名(Post Name | Slug)，如果选择此项，请确保文章名(Post Name | Slug)不包含中文以及其他东亚字符。

= 1.7.11.4 =
Bug fixed:<br />
About image maximum size filter settings, support any size of one of width and height.<br />
修复Bug：<br />
在图片尺寸过滤设置中，支持高度和宽度其中之一为任意尺寸。

= 1.7.11.3 =
Bug fixed:<br />
Select category doesn't works on scan feature.<br />
修复Bug：<br />
扫描文章功能的选择分类不能正常工作。

= 1.7.11.2 =
Bug fixed:<br />
Can't format image url that has (*).<br />
修复Bug：<br />
不能格式化带*号的图片链接

= 1.7.11.1 =
Bug fixed:<br />
Ignore PHP warning messages has missing images when scanning posts.<br />
修复Bug：<br />
扫描文章时忽略远程图片不存在的警告信息。

= 1.7.11 =
New feature:<br />
Display addistional content after the each remote images.<br />
新特性：<br />
显示额外的内容到每一个自动保存的远程图片后面。

= 1.7.10.1 =
New feature:<br />
If PHP version lower than 5.4, automatic disabled the Maximum-Picture-Size feature.<br />
新特性：<br />
如果PHP版本低于5.4，自动禁用设置最大图像的功能。

= 1.7.10 =
New feature:<br />
Automatic reduction is greater than the size of the picture. default: 1280x1280(px)<br />
新特性：<br />
自动缩小大于所设置尺寸的图像。默认：1280x1280(像素)

= 1.7.9 =
New feature:<br />
Allowed to Ignore small size picture, such as some icons. default: 32x32(px)<br />
新特性：<br />
允许忽略小尺寸图像，比如一些小图标。默认：32x32(像素)

= 1.7.8 =
<ul>
	<li>New features:
		<ul>
			<li>Ignore animated GIF when doing watermark as preview option.</li>
			<li>Modified 'http://' to 'http(s)://' in Exclude-Domain/Keyword of General Options.</li>
		</ul>
	</li>
	<li>新特性:
		<ul>
			<li>添加水印时忽略GIF动画的预览选项</li>
			<li>将常规选项中的排除域名/关键词中的'http://'改为'http(s)://'</li>
		</ul>
	</li>
</ul>

= 1.7.7 =
New features:<br />
Allowed to keeps outside links of remote images.<br />
新特性：<br />
允许保留远程图片的外部链接。

= 1.7.6 =
Fixed a bug of regular expression that cleaned many contents in Visual-mode of Editor and in Manual-mode of plugin.<br />
修复正则表达式的bug，在可视化编辑情况下手动保存图片有时候会清空大量的内容。

= 1.7.5 =
New features:<br />
Added format options. The <img> code formated just like insert to post.<br />
新特性：<br />
增加格式化选项。格式化的<img>代码就像插入到文章的。

= 1.7.4 =
<ul>
	<li>Bugs fixed:
		<ol>
			<li>Manual mode of Scan-Post did saved remote images in process.</li>
			<li>Javascript parse error on select checkbox of pages of scan-posts in English website language.</li>
		</ol>
	</li>
	<li>New features:
		<ul>
			<li>Preview watermark.</li>
		</ul>
	</li>
	<li>修复Bugs：
		<ol>
			<li>扫描文章的手动模式会在扫描过程中偷偷保存远程图片。</li>
			<li>站点语言为英文时，选择扫描文章的页面文章类型会报Javascript语法错误。</li>
		</ol>
	</li>
	<li>新特性:
		<ul>
			<li>水印功能预览</li>
		</ul>
	</li>
</ul>

= 1.7.3 =
Bug fixed:<br />
The bug that Added a backslash "\" in front of apostrophes (') in text mode of editor and in manual mode of plugin.<br />
修复Bug：<br />
在插件的手动模式和编辑器的文本模式下，在(')之前加上"\"

= 1.7.2 =
New features:<br />
In manual mode, Supported auto save images from remote publishing.<br />
Bugs fixed:<br />
Some dynamic links had problem with change name.<br /><br />
新特性：<br />
支持在手动模式下允许通过远程发布保存图片。<br />
修复Bugs：<br />
采集一些动态链接出现Bug

= 1.7.1 =
<ul>
	<li>New features:
		<ol>
			<li>Auto change image filename, If it's possible to encounter remote images filename containing Chinese or other East Asian characters, i suggest enable it.</li>
			<li>Added Order/Order-by/Status filters for scan-posts mode.</li>
		</ol>
	</li>
	<li>新特性：
		<ol>
			<li>自动图片文件更名，如果你有可能碰到包含中文或其他东亚字符的远程图片文件名，建议开启。</li>
			<li>扫描文章模式增加 排序/排序依据/状态 筛选。</li>
		</ol>
	</li>
</ul>

= 1.7 =
<ul>
	<li>New features:
		<ol>
			<li>New interface.</li>
			<li>Added categories filter for scan-posts mode.</li>
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
New feature: Debug system added<br />
新特性：增加调试系统

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

= 1.7.10 =
This version used a new GD2 function, you need at least PHP 5.4 to run it.
该版本使用了一个新GD2函数，你至少需要PHP5.4来运行。

= 1.1 =
if you want to upload images with chinese file name and server does not support chinese filename, please using 1.0.<br />
如果需要上传中文名图片，服务器又不支持中文文件名，请使用1.0

= 1.0 =
The feature is can automatically change image filename.<br />
特色是可以自动更改图片的文件名

== Frequently Asked Questions ==

= Why the Maximum-Picture-Size fields are gray? =

Because PHP version is lower than 5.4. please upgrade your PHP.

= Why the Watermark featrue does not works? =

Because of it's only a preview. Full functionality is being developed in the Professional Edition. and i don't know what time Professional Edition released. who cares..

= 为什么“最大图像尺寸”设置输入框是灰色的？ =

因为PHP版本低于5.4，请升级PHP。

= 为什么水印功能不能用？ =

因为只是预览，完整的功能将在开发中的专业版中。我也不知道专业什么时候发布，管它呢……