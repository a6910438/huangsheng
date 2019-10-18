/**
 * @Time    2018-08-30 14:56:00
 * @Email   chuaer@foxmail.com
 * @Author  Huaer
 * @Explain core.js
 */
;(function (){
	// 定义属性变量
	function core(){
        this.errMsg = '{status}服务器繁忙，请稍候再试！';
        this.toastMsg = '消息提示内容';
        this.loadMsg = '数据加载中';
        this.promptMsg = '请输入内容';
        this.confirmMsg = '确认';
        this.cancelMsg = '取消';
	};
	// 方法挂载在原型链上
	core.prototype = {
		load: function(url, data, callback, type, loading){// 获取json数据
			var self = this;
			$.ajax({
                url: url || window.location.href,
                type: type || 'POST',
                data: data || {},
                dataType: 'json',
                beforeSend: function(){(loading !== undefined ? loading : true) && self.loading();},
                complete: function(){(loading !== undefined ? loading : true) && self.rmLoading();},
                success: function(res){
                    (typeof callback === 'function') && callback(res);
                },
                error: function(XHR, textStatus, errorThrown) {
                    self.toast(self.errMsg.replace('{status}', 'E' + textStatus + ' - '));
                }
            });
		},
		alert: function(msg, tit, callback){// 提示消息框
			this.tplHtml(msg, tit, callback, false);
		},
		confirm: function(msg, tit, callback){// 确认消息框
			this.tplHtml(msg, tit, callback, true);
		},
		prompt: function(msg, tit, callback, prompt){// 输入对话框
			this.tplHtml(msg, tit, callback, true, prompt || this.promptMsg);
		},
		toast: function(msg, position, duration){// 自动消失提示框
			var msg = msg || this.toastMsg;
			var position = position || 'bottom';
			var duration = isNaN(duration) ? 1000 : duration;
			if (duration <= 0) {return;}
            var html = '<div id="toast">'+msg+'</div>';
            var div = $(html);
			$(document.body).append(div);
			var css = '';
			if (position == 'top') {
                css = "top:10%";
            }else if (position == 'middle') {
                css = "top:50%";
            }else{
                css = "bottom:10%";
            }
            div.attr('style',css);
			setTimeout(function() {
                div.css({transition: '-webkit-transform .2s ease-in, opacity .2s ease-in',opacity: '0'});
                setTimeout(function(){div.remove()},200);
            }, duration);
		},
		loading: function(msg){// 加载弹框
			var msg = msg || this.loadMsg;
			var html = '<div id="loading"><div class="load-panel"><i class="load-icon loading"></i><p class="load-content">'+msg+'</p></div></div>';
			var div = $(html);
			$('#loading').length<=0 && $(document.body).append(div);
		},
		rmLoading: function(duration){// 删除加载弹框
			var duration = isNaN(duration) ? 300 : duration;
			var div = $('#loading');
			if (duration <= 0) {return;}
			if(!div){return;}
			setTimeout(function() {
                div.css({transition: '-webkit-transform .2s ease-in, opacity .2s ease-in',opacity: '0'});
                setTimeout(function(){div.remove()},200);
            }, duration);
		},
		tplHtml: function(msg, tit, callback, isClose, prompt){// 消息框公用方法
			var msg = msg || this.toastMsg;
			var tit = tit || '';
			var html = '';
			html+='<div id="warning"><div class="warn-panel">';
			if(tit){html+='<div class="warn-hd"><strong>'+tit+'</strong></div>';}
			html+='<div class="warn-bd">';
			html+='<p>'+msg+'</p>';
			if(prompt){html+='<p><input type="text" value="'+prompt+'" placeholder="'+prompt+'" /></p>';}
			html+='</div><div class="warn-ft">';
			if(isClose){html+='<a href="javascript:;" class="warn-btn warn-default">'+this.cancelMsg+'</a>'}
			html+='<a href="javascript:;" class="warn-btn warn-primary">'+this.confirmMsg+'</a></div></div>';
			var div = $(html);
			$(document.body).append(div);
			div.off('click').on('click', '.warn-btn', function(){
				div.css({transition: '-webkit-transform .2s ease-in, opacity .2s ease-in',opacity: '0'});
                setTimeout(function(){div.remove()},200);
                var val = div.find('input').val() || '';
				$(this).hasClass('warn-primary') && (typeof callback === 'function') && callback(val);
			});
		},
		getParame: function(name){// 获取路由的参数
            var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
            var r = window.location.search.substr(1).match(reg);
            if(r){
            	return unescape(r[2]);
            }else{
            	return null;
            }
        }
	};
	// 挂载到把常用的方法core上
	window.core = new core();
})();