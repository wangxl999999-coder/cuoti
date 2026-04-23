App({
  globalData: {
    userInfo: null,
    token: '',
    baseUrl: 'https://cuoti.com/api',
    grades: [],
    subjects: []
  },

  onLaunch: function () {
    const token = wx.getStorageSync('token');
    if (token) {
      this.globalData.token = token;
      this.getUserInfo();
    }
    
    this.loadConfig();
  },

  loadConfig: function() {
    const that = this;
    
    this.request({
      url: '/grade/list',
      method: 'GET',
      success: (res) => {
        if (res.code === 200) {
          that.globalData.grades = res.data;
        }
      }
    });
    
    this.request({
      url: '/subject/list',
      method: 'GET',
      success: (res) => {
        if (res.code === 200) {
          that.globalData.subjects = res.data;
        }
      }
    });
  },

  getUserInfo: function() {
    if (!this.globalData.token) {
      return;
    }
    
    const that = this;
    this.request({
      url: '/user/info',
      method: 'GET',
      success: (res) => {
        if (res.code === 200) {
          that.globalData.userInfo = res.data;
        }
      }
    });
  },

  request: function(options) {
    const that = this;
    const url = this.globalData.baseUrl + options.url;
    
    wx.showLoading({
      title: '加载中...',
      mask: true
    });
    
    return new Promise((resolve, reject) => {
      wx.request({
        url: url,
        method: options.method || 'GET',
        data: options.data || {},
        header: {
          'Content-Type': 'application/json',
          'token': that.globalData.token
        },
        success: (res) => {
          wx.hideLoading();
          
          if (res.statusCode === 200) {
            if (res.data.code === 401) {
              that.logout();
              wx.showToast({
                title: '登录已过期，请重新登录',
                icon: 'none'
              });
              setTimeout(() => {
                wx.reLaunch({
                  url: '/pages/login/login'
                });
              }, 1500);
              return;
            }
            
            if (options.success) {
              options.success(res.data);
            }
            resolve(res.data);
          } else {
            wx.showToast({
              title: '网络错误',
              icon: 'none'
            });
            if (options.fail) {
              options.fail(res);
            }
            reject(res);
          }
        },
        fail: (err) => {
          wx.hideLoading();
          wx.showToast({
            title: '网络请求失败',
            icon: 'none'
          });
          if (options.fail) {
            options.fail(err);
          }
          reject(err);
        }
      });
    });
  },

  uploadFile: function(options) {
    const that = this;
    const url = this.globalData.baseUrl + options.url;
    
    wx.showLoading({
      title: '上传中...',
      mask: true
    });
    
    return new Promise((resolve, reject) => {
      wx.uploadFile({
        url: url,
        filePath: options.filePath,
        name: options.name || 'file',
        header: {
          'token': that.globalData.token
        },
        formData: options.formData || {},
        success: (res) => {
          wx.hideLoading();
          
          if (res.statusCode === 200) {
            const data = JSON.parse(res.data);
            
            if (data.code === 401) {
              that.logout();
              wx.showToast({
                title: '登录已过期，请重新登录',
                icon: 'none'
              });
              setTimeout(() => {
                wx.reLaunch({
                  url: '/pages/login/login'
                });
              }, 1500);
              return;
            }
            
            if (options.success) {
              options.success(data);
            }
            resolve(data);
          } else {
            wx.showToast({
              title: '上传失败',
              icon: 'none'
            });
            if (options.fail) {
              options.fail(res);
            }
            reject(res);
          }
        },
        fail: (err) => {
          wx.hideLoading();
          wx.showToast({
            title: '上传失败',
            icon: 'none'
          });
          if (options.fail) {
            options.fail(err);
          }
          reject(err);
        }
      });
    });
  },

  logout: function() {
    this.globalData.token = '';
    this.globalData.userInfo = null;
    wx.removeStorageSync('token');
    wx.removeStorageSync('userInfo');
  },

  checkLogin: function() {
    if (!this.globalData.token) {
      wx.showModal({
        title: '提示',
        content: '请先登录',
        showCancel: false,
        success: () => {
          wx.reLaunch({
            url: '/pages/login/login'
          });
        }
      });
      return false;
    }
    return true;
  },

  showPointsAnimation: function(points, callback) {
    const animation = wx.createAnimation({
      duration: 500,
      timingFunction: 'ease'
    });

    animation.opacity(1).scale(1.2).step();
    animation.scale(1).step();
    
    if (callback) {
      callback(animation);
    }
  }
});
