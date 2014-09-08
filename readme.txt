=== QQWorld Auto Save Images ===
Contributors: Michael Wang
Tags: auto, save, local, collection, images
Requires at least: 3.0
Tested up to: 4.0
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically keep the all remote picture to the local, and automatically set featured image.自动保存远程图片到本地，自动设置特色图片，并且支持机器人采集软件从外部提交。

== Description ==

Automatically keep the all remote picture to the local, and automatically set featured image.

This plugin has a big problem that is all the image url must be full url, it means must include "http(s)://", for example:

<strong><em>&lt;img src="http://img.whitehouse.gov/image/2014/08/09/gogogo.jpg" /&gt;</em></strong>

The examples not working code:

<strong><em>&lt;img src="/images/great.png" /&gt;<br />
&lt;img src="./photo-lab/2014-08-09.jpg" /&gt;<br />
&lt;img src="img/background/black.gif" /&gt;</em></strong>

I was tried to fix this out, but i can't let the program get the host name to full the image src.

So if you encounter these codes, plaese manually fix the images src to full url.

-----------------------------------------------------------------------------------------------

自动保存远程图片到本地，自动设置特色图片，并且支持机器人采集软件从外部提交。

该插件有个大问题是，所有的图片URL必须是完整的，就是说必须得包含"http(s)://"，举例：

<strong><em>&lt;img src="http://img.whitehouse.gov/image/2014/08/09/gogogo.jpg" /&gt;</em></strong>

不能保存图片的例子：

<strong><em>&lt;img src="/images/great.png" /&gt;<br />
&lt;img src="./photo-lab/2014-08-09.jpg" /&gt;<br />
&lt;img src="img/background/black.gif" /&gt;</em></strong>

我尝试解决这个问题，但是我无法让程序获取到主机名从而让图片的URL完整。
所以，如果你碰到这样的代码，请手动让图片地址改成完整的，或者使用采集工具自动补完图片的URL然后从外部提交给Wordpress。

== Installation ==

1. 上传 `qqworld-auto-save-images` 目录到 `/wp-content/plugins/` 文件夹
1. 在wordpress的 '插件' 菜单中激活该插件

== Changelog ==

= 1.2 =
* add admin form. 添加后台表单来管理选择使用下载远程图片的动作。

= 1.1 =
* Remove auto rename. 移除自动更名功能。

= 1.0 =
* 为采集而生

== Upgrade Notice ==

= 1.1 =
如果需要上传中文名图片，服务器又不支持中文文件名，请使用1.0

= 1.0 =
特色是可以自动更改图片的文件名