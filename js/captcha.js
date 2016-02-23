(function(){
	var captchaApi = window.CONFIG && CONFIG.CAPTCHA_HOST;

	var Captcha = function(el, conf){
		var $el = $(el),
			tagName = $el.prop('tagName');		
		this.conf = $.extend({
			captchaApi : captchaApi,
			id         : 'captcha-' + Captcha.id++,
			className  : 'captcha',
			refreshEl  : '.refresh'
		}, conf);
		if(tagName === 'IMG'){
			this.wrap = $(el).parent();
			this.img   = $el;
		} else {
			this.wrap = $el;
			this.img  = $el.find('img.' + this.conf.className);
			this.img  = this.img.length ? this.img : $el.find('img');
		}

		this.init();
	}
	Captcha.id = 0;
	$.extend(Captcha.prototype, {
		genSrc : function(){
			return this.conf.captchaApi + '?' + (+new Date);
		},
		createImg : function(){
			var conf = this.conf;
			var img = $('<img src="' + this.genSrc() + '" class="' + conf.className + '" id="' + conf.id + '" al="验证码" title="点击图片刷新验证码">');
			return img;
		},
		refresh : function(){
			this.img.attr('src', this.genSrc());
			return this;
		},
		load : function(){
			if(this.loaded){
				return;
			}
			if(!this.img.length){
				var img = this.createImg();	
				this.wrap.append(img);
				this.img = img;
			} else if(!this.img.attr('src')) {
				this.refresh();
			}
			var self = this;
			this.img.css('cursor', 'pointer');
			this.img.on('click', function(e){
				e.preventDefault();
				self.refresh();
			});
			this.loaded = true;
			return this;
		},		
		init : function(){
			var self = this;
			this.loaded = false;
			this.wrap.delegate(this.conf.refreshEl, 'click', function(e){
				e.preventDefault();
				self.refresh();
			});
		}
	});
	$.extend($.fn, {
		captcha : function(conf){
			return new Captcha(this, conf).load();
		}
	});

	window.Captcha  = Captcha;
})();