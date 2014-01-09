SAEChannel.Chatbox
==================

使用SAE的Channel服务写的聊天室程序，使用jquery、php、easyui还有SAE的channel、mysql、KVDB等服务实现，同时利用了疯子好好活提供的聊天机器人。
加入了私聊功能，登录之后显示最后40条聊天记录（私聊内容不会被记录）。
需要数据库的支持：

CREATE TABLE IF NOT EXISTS `msg_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `msg` varchar(500) NOT NULL,
  `uname` varchar(30) NOT NULL,
  `action` varchar(10) NOT NULL,
  `send_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=437 ;
