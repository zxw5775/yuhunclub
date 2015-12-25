(function(){
	var passportApi    = window.CONFIG && CONFIG.PASSPORT_API_HOST;
	var encryptTimeout = window.CONFIG && CONFIG.ENCRYPT_TIMEOUT;
	var encrypt = new JSEncrypt();
	var ERROR = {
		OK            : 1,
		GET_KEY_ERROR : -2001,
		API_ERROR     : -2002,
		VERIFY_ERROR  : -1001,
		CAPTCHA_ERROR : -1002
	}

	var getPubkey = (function(){
		var timeout  = encryptTimeout || 120000;
		var lastTime = new Date,
			key;		
		var ret = function(){
			var defer = new $.Deferred();
			if(!key || new Date - lastTime >= timeout){
				$.ajax(passportApi + 'get_key', {dataType : 'jsonp'}).done(function(d){
					var publicKey = d.data.encrypt.public_key;
					if(d.code === 1 && publicKey){
						defer.resolve(publicKey);
						key      = publicKey;
					} else {
						defer.reject({code : ERROR.GET_KEY_ERROR});
					}
				}).fail(function(){
					defer.reject({code : ERROR.GET_KEY_ERROR});
				}).always(function(){
					lastTime = new Date;
				});
			} else {
				defer.resolve(key);
			}
			return defer.promise();
		}
		ret.reset = function(){
			key = null;
		}
		return ret;
	})();

	var Passport = {
		_getApi : function(action, data, encode){
			var defer = new $.Deferred(),
				tobeEncodeKeys = ['name', 'password', 'old_password', 'new_password'],
				hasEncode = false,
				encode = (typeof encode === 'undefined') || encode;
			(encode ? getPubkey() : $.when()).then(function(publishKey){
				publishKey && encrypt.setPublicKey(publishKey);
				if(encode){
					for(var l = tobeEncodeKeys.length; l--;){
						key = tobeEncodeKeys[l];
						if(data[key]){
							data[key] = encrypt.encrypt(data[key]);
							hasEncode = true;
						}
					}
				}
				$.ajax(passportApi + action, {
					dataType   :'jsonp',
					data       : data
				}).done(function(d){
					if(d.code === 1){
						defer.resolve(d.data);
					} else {
						defer.reject({
							code : d.code,
							msg  : d.message
						});
					}
				}).fail(function(){
					defer.reject({code : ERROR.API_ERROR});
				}).always(function(){
					hasEncode && getPubkey.reset();
				});
			}).fail(function(err){
				defer.reject(err);
			});
			return defer.promise();
		},
		login : function(name, password, code){
			return Passport._getApi('login', {
				name        : name,
				password    : password,
				verify_code : code
			});
		},
		register : function(name, password, code){
			return Passport._getApi('register', {
				name        : name,
				password    : password,
				verify_code : code
			});
		},
		bindEmailSendCode : function(email){
			return Passport._getApi('safe/bind_email_send_code', {
				email : email
			}, false);
		},
		bindEmail : function(email, token){
			return Passport._getApi('safe/bind_email', {
				token : token,
				email : email
			}, false);
		},
		bindMobileSendCode : function(mobile){
			return Passport._getApi('safe/bind_mobile_send_code', {
				mobile : mobile
			}, false);
		},
		bindMobile : function(mobile, vcode){
			return Passport._getApi('safe/bind_mobile', {
				mobile : mobile,
				vcode  : vcode
			}, false);
		},
		checkName : function(name){
			return Passport._getApi('check_name', {
				name : name
			}, false);
		},
		activate :function(name, password, code){
			return Passport._getApi('safe/activate', {
				name     : name,
				password : password,
				verify_code : code
			});
		},
		updatePassword : function(oldpassword, newpassword, code){
			return Passport._getApi('safe/update_password', {
				old_password : oldpassword,
				new_password : newpassword,
				verify_code  : code
			});
		},
		preGetKey : function(){
			getPubkey();
		},
		ERROR : ERROR
	}

	window.Passport = Passport;
})();
