=== QQWorld Auto Save Images ===
Contributors: Michael Wang
Tags: auto, save, local, fetch, images
Requires at least: 3.0
Tested up to: 4.0
Stable tag: 1.4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically keep the all remote picture to the local media libary when you publishing posts, and automatically set featured image.

== Description ==

Automatically keep the all remote picture to the local media libary when you publishing posts, and automatically set featured image.

This plugin has a little problem that is all the image url must be full url, it means must include "http(s)://", for example:

<ul>
<li>&lt;img src=&quot;http://img.whitehouse.gov/image/2014/08/09/gogogo.jpg&quot; /&gt;</li>
</ul>

The examples not working code:

<ul>
<li>&lt;img src=&quot;/images/great.png&quot; /&gt;</li>
<li>&lt;img src=&quot;./photo-lab/2014-08-09.jpg&quot; /&gt;</li>
<li>&lt;img src=&quot;img/background/black.gif&quot; /&gt;</li>
</ul>

I tried to fix this out, but i can't let the program get the host name to full the image src.

So if you encounter these codes, plaese manually fix the images src to full url.

<hr />

在发布文章时自动保存远程图片到本地媒体库，自动设置特色图片，并且支持机器人采集软件从外部提交。

该插件有个小问题是，所有的图片URL必须是完整的，就是说必须得包含"http(s)://"，举例：

<ul>
<li>&lt;img src=&quot;http://img.whitehouse.gov/image/2014/08/09/gogogo.jpg&quot; /&gt;</li>
</ul>

不能保存图片的例子：

<ul>
<li>&lt;img src=&quot;/images/great.png&quot; /&gt;</li>
<li>&lt;img src=&quot;./photo-lab/2014-08-09.jpg&quot; /&gt;</li>
<li>&lt;img src=&quot;img/background/black.gif&quot; /&gt;</li>
</ul>

我尝试解决这个问题，但是我无法让程序获取到主机名从而让图片的URL完整。
所以，如果你碰到这样的代码，请手动将图片地址改成完整的，或者使用采集工具自动补完图片的URL然后从外部提交给Wordpress。

== Installation ==

No special needs description
没有特别需要说明的

== Changelog ==

= 1.4.2 =
Fix a bug: If remote images are too big or too many, program need more time to download, When default time out, whole process stoped.
If remote images are too many, sometimes you need fetch twice.
修复了一个bug：如果远程图片太大或太多，程序需要更多时间来下载，当默认的运行超时，整个过程停止。
如果远程图片太多，有时候需要抓取两次。

= 1.4.1 =
New functional optimized
优化了新功能

= 1.4 =
Add a new type allow user manually save remote images via click a button on the top of editor, and don't forget after you saved images, you still need to submit the post.
You can change the Type in the plugin's setting page.

添加一个新类型允许用户通过单击编辑器顶部的按钮手动保存远程图像，别忘记了在保存图片后提交文章哦。
你可以在插件的设置页面更改类型。

= 1.3 =
Fixed 2 bugs:
<ol>
<li>Failed to save remote image to local when the image url include "(\?(.*?))?", now it's works.</li>
<li>If the remote image url is invalid, will not to save it to a blank attachment.</li>
</ol>
修复两个bug:
<ol>
<li>如果远程图片地址包含 "(\?(.*?))?" 会无法保存到本地，现在工作正常。</li>
<li>如果远程图片地址无效，则不会保存一个空的附件到本地。</li>
</ol>

= 1.2 =
add admin page to control use which action to save remote image.
添加后台页面来管理选择使用下载远程图片的动作。

= 1.1 =
Remove auto rename.
移除自动更名功能。
if you want to upload images with chinese file name and server does not support chinese filename, please using 1.0.
如果需要上传中文名图片，服务器又不支持中文文件名，请使用1.0

= 1.0 =
Born for Capture.
为采集而生
The feature is can automatically change image filename.
特色是可以自动更改图片的文件名

== Upgrade Notice ==

= 1.1 =
if you want to upload images with chinese file name and server does not support chinese filename, please using 1.0.
如果需要上传中文名图片，服务器又不支持中文文件名，请使用1.0

= 1.0 =
The feature is can automatically change image filename.
特色是可以自动更改图片的文件名