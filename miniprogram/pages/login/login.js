const app = getApp();

Page({
  data: {
    phone: '',
    password: ''
  },

  onLoad: function(options) {
    const token = wx.getStorageSync('token');
    if (token) {
      app.globalData.token = token;
      this.checkLogin();
    }
  },

  onPhoneInput: function(e) {
    this.setData({
      phone: e.detail.value
    });
  },

  onPasswordInput: function(e) {
    this.setData({
      password: e.detail.value
    });
  },

  onLogin: function() {
    const { phone, password } = this.data;
    
    if (!phone) {
      wx.showToast({
        title: '请输入手机号',
        icon: 'none'
      });
      return;
    }
    
    if (!/^1[3-9]\d{9}$/.test(phone)) {
      wx.showToast({
        title: '手机号格式不正确',
        icon: 'none'
      });
      return;
    }
    
    if (!password) {
      wx.showToast({
        title: '请输入密码',
        icon: 'none'
      });
      return;
    }
    
    const that = this;
    app.request({
      url: '/user/login',
      method: 'POST',
      data: {
        phone: phone,
        password: password
      },
      success: function(res) {
        if (res.code === 200) {
          app.globalData.token = res.data.token;
          app.globalData.userInfo = res.data.user;
          wx.setStorageSync('token', res.data.token);
          wx.setStorageSync('userInfo', res.data.user);
          
          wx.showToast({
            title: '登录成功',
            icon: 'success'
          });
          
          setTimeout(() => {
            wx.switchTab({
              url: '/pages/index/index'
            });
          }, 1500);
        } else {
          wx.showToast({
            title: res.msg,
            icon: 'none'
          });
        }
      }
    });
  },

  onWechatLogin: function() {
    const that = this;
    wx.login({
      success: function(res) {
        if (res.code) {
          wx.getUserProfile({
            desc: '用于完善用户资料',
            success: function(profileRes) {
              that.wechatLogin(res.code, profileRes.userInfo);
            },
            fail: function() {
              that.wechatLogin(res.code, null);
            }
          });
        } else {
          wx.showToast({
            title: '登录失败',
            icon: 'none'
          });
        }
      }
    });
  },

  wechatLogin: function(code, userInfo) {
    const that = this;
    wx.request({
      url: 'https://api.weixin.qq.com/sns/jscode2session',
      data: {
        appid: app.globalData.appId || 'your_appid',
        secret: app.globalData.appSecret || 'your_secret',
        js_code: code,
        grant_type: 'authorization_code'
      },
      success: function(res) {
        if (res.data.openid) {
          app.request({
            url: '/user/login',
            method: 'POST',
            data: {
              openid: res.data.openid
            },
            success: function(loginRes) {
              if (loginRes.code === 200) {
                app.globalData.token = loginRes.data.token;
                app.globalData.userInfo = loginRes.data.user;
                wx.setStorageSync('token', loginRes.data.token);
                wx.setStorageSync('userInfo', loginRes.data.user);
                
                wx.showToast({
                  title: '登录成功',
                  icon: 'success'
                });
                
                setTimeout(() => {
                  wx.switchTab({
                    url: '/pages/index/index'
                  });
                }, 1500);
              } else if (loginRes.data && loginRes.data.need_register) {
                wx.setStorageSync('temp_openid', res.data.openid);
                wx.showToast({
                  title: '请先注册账号',
                  icon: 'none'
                });
                setTimeout(() => {
                  wx.navigateTo({
                    url: '/pages/register/register'
                  });
                }, 1500);
              } else {
                wx.showToast({
                  title: loginRes.msg,
                  icon: 'none'
                });
              }
            }
          });
        }
      }
    });
  },

  checkLogin: function() {
    app.request({
      url: '/user/info',
      method: 'GET',
      success: function(res) {
        if (res.code === 200) {
          app.globalData.userInfo = res.data;
          wx.setStorageSync('userInfo', res.data);
          wx.switchTab({
            url: '/pages/index/index'
          });
        }
      }
    });
  },

  goToRegister: function() {
    wx.navigateTo({
      url: '/pages/register/register'
    });
  }
});
