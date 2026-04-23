const app = getApp();

Page({
  data: {
    userInfo: null,
    checkinInfo: null,
    statistics: {
      total: 0,
      mastered: 0,
      unmastered: 0,
      mastered_rate: 0,
      subject_list: []
    },
    maxSubjectCount: 1,
    showPointsAnimation: false,
    earnedPoints: 0,
    pointsAnimation: null
  },

  onLoad: function(options) {
    this.checkLogin();
  },

  onShow: function() {
    const token = wx.getStorageSync('token');
    if (token) {
      app.globalData.token = token;
      this.loadData();
    }
  },

  checkLogin: function() {
    const token = wx.getStorageSync('token');
    if (!token) {
      wx.reLaunch({
        url: '/pages/login/login'
      });
      return;
    }
    
    app.globalData.token = token;
    this.loadData();
  },

  loadData: function() {
    this.loadUserInfo();
    this.loadCheckinInfo();
    this.loadStatistics();
  },

  formatUserInfo: function(userInfo) {
    if (!userInfo) return userInfo;
    const formatted = Object.assign({}, userInfo);
    formatted._nicknameFirstChar = formatted.nickname ? formatted.nickname.charAt(0) : '用';
    return formatted;
  },

  loadUserInfo: function() {
    const that = this;
    const userInfo = wx.getStorageSync('userInfo');
    
    if (userInfo) {
      that.setData({ userInfo: that.formatUserInfo(userInfo) });
    }
    
    app.request({
      url: '/user/info',
      method: 'GET',
      success: function(res) {
        if (res.code === 200) {
          const formattedUser = that.formatUserInfo(res.data);
          that.setData({ userInfo: formattedUser });
          app.globalData.userInfo = res.data;
          wx.setStorageSync('userInfo', res.data);
        }
      }
    });
  },

  loadCheckinInfo: function() {
    const that = this;
    app.request({
      url: '/checkin/today',
      method: 'GET',
      success: function(res) {
        if (res.code === 200) {
          that.setData({ checkinInfo: res.data });
        }
      }
    });
  },

  loadStatistics: function() {
    const that = this;
    app.request({
      url: '/wrong-question/statistics',
      method: 'GET',
      success: function(res) {
        if (res.code === 200) {
          let maxCount = 1;
          if (res.data.subject_list && res.data.subject_list.length > 0) {
            maxCount = Math.max(...res.data.subject_list.map(s => s.count), 1);
          }
          
          that.setData({
            statistics: res.data,
            maxSubjectCount: maxCount
          });
        }
      }
    });
  },

  doCheckin: function() {
    const that = this;
    wx.showLoading({
      title: '签到中...',
      mask: true
    });
    
    app.request({
      url: '/checkin/checkin',
      method: 'POST',
      success: function(res) {
        wx.hideLoading();
        
        if (res.code === 200) {
          that.setData({
            showPointsAnimation: true,
            earnedPoints: res.data.points_earned
          });
          
          that.playPointsAnimation();
          
          that.setData({
            checkinInfo: {
              is_checked: true,
              continuous_days: res.data.continuous_days,
              today_points: res.data.points_earned
            }
          });
          
          if (that.data.userInfo) {
            that.setData({
              'userInfo.points': that.data.userInfo.points + res.data.points_earned
            });
          }
          
          setTimeout(() => {
            that.setData({ showPointsAnimation: false });
          }, 2000);
        } else {
          wx.showToast({
            title: res.msg,
            icon: 'none'
          });
        }
      },
      fail: function() {
        wx.hideLoading();
      }
    });
  },

  playPointsAnimation: function() {
    const animation = wx.createAnimation({
      duration: 300,
      timingFunction: 'ease-out'
    });
    
    animation.scale(1).opacity(1).step();
    this.setData({
      pointsAnimation: animation.export()
    });
    
    setTimeout(() => {
      animation.scale(1.2).step({ duration: 200 });
      animation.scale(1).step({ duration: 200 });
      this.setData({
        pointsAnimation: animation.export()
      });
    }, 300);
  },

  goToPoints: function() {
    wx.navigateTo({
      url: '/pages/points/index'
    });
  },

  goToAddQuestion: function() {
    if (!app.checkLogin()) return;
    wx.navigateTo({
      url: '/pages/wrong-question/add'
    });
  },

  goToWrongList: function() {
    if (!app.checkLogin()) return;
    wx.switchTab({
      url: '/pages/wrong-question/list'
    });
  },

  goToPractice: function() {
    if (!app.checkLogin()) return;
    wx.switchTab({
      url: '/pages/practice/index'
    });
  },

  goToCollection: function() {
    if (!app.checkLogin()) return;
    wx.navigateTo({
      url: '/pages/collection/list'
    });
  },

  onPullDownRefresh: function() {
    this.loadData();
    setTimeout(() => {
      wx.stopPullDownRefresh();
    }, 1000);
  }
});
