const app = getApp();

Page({
  data: {
    userInfo: null
  },

  onLoad: function(options) {
    this.loadUserInfo();
  },

  onShow: function() {
    this.loadUserInfo();
  },

  loadUserInfo: function() {
    const userInfo = wx.getStorageSync('userInfo');
    if (userInfo) {
      this.setData({ userInfo: userInfo });
    }
    
    const that = this;
    app.request({
      url: '/user/info',
      method: 'GET',
      success: function(res) {
        if (res.code === 200) {
          that.setData({ userInfo: res.data });
          wx.setStorageSync('userInfo', res.data);
        }
      }
    });
  },

  goToPoints: function() {
    wx.navigateTo({
      url: '/pages/points/index'
    });
  },

  goToCollection: function() {
    wx.navigateTo({
      url: '/pages/collection/list'
    });
  },

  goToCheckin: function() {
    wx.navigateTo({
      url: '/pages/points/index'
    });
  },

  goToEditProfile: function() {
    wx.showModal({
      title: '提示',
      content: '个人设置功能开发中，敬请期待',
      showCancel: false
    });
  },

  showFeedback: function() {
    wx.showModal({
      title: '意见反馈',
      content: '如有任何问题或建议，请联系客服',
      showCancel: false
    });
  },

  showAbout: function() {
    wx.showModal({
      title: '关于我们',
      content: '错题本是一款专为中小学生设计的学习辅助工具，通过拍照记录错题、AI智能学习、举一反三练习，帮助学生高效学习，提升成绩。',
      showCancel: false
    });
  },

  doLogout: function() {
    const that = this;
    wx.showModal({
      title: '提示',
      content: '确定要退出登录吗？',
      success: function(res) {
        if (res.confirm) {
          app.request({
            url: '/user/logout',
            method: 'POST',
            success: function() {
              app.logout();
              wx.reLaunch({
                url: '/pages/login/login'
              });
            }
          });
        }
      }
    });
  }
});
