const app = getApp();

Page({
  data: {
    userInfo: null,
    statistics: {
      total_earned: 0,
      correct_count: 0,
      checkin_count: 0
    },
    logList: [],
    currentType: 0,
    page: 1,
    pageSize: 10,
    loading: false,
    noMore: false,
    showPointsAnimation: false,
    earnedPoints: 0,
    pointsAnimation: null
  },

  onLoad: function(options) {
    this.loadUserInfo();
    this.loadStatistics();
    this.loadLogs();
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

  loadStatistics: function() {
    const that = this;
    app.request({
      url: '/points/statistics',
      method: 'GET',
      success: function(res) {
        if (res.code === 200) {
          that.setData({ statistics: res.data });
        }
      }
    });
  },

  loadLogs: function() {
    if (this.data.loading || this.data.noMore) return;
    
    this.setData({ loading: true });
    
    const that = this;
    const { page, pageSize, currentType } = this.data;
    
    const params = {
      page: page,
      page_size: pageSize
    };
    
    if (currentType > 0) {
      params.type = currentType;
    }
    
    app.request({
      url: '/points/logs',
      method: 'GET',
      data: params,
      success: function(res) {
        if (res.code === 200) {
          const newList = res.data.list || [];
          const total = res.data.total || 0;
          
          that.setData({
            logList: that.data.logList.concat(newList),
            loading: false,
            noMore: newList.length < pageSize || that.data.logList.length + newList.length >= total,
            page: that.data.page + 1
          });
        }
      },
      fail: function() {
        that.setData({ loading: false });
      }
    });
  },

  selectType: function(e) {
    const type = e.currentTarget.dataset.type;
    this.setData({
      currentType: type,
      page: 1,
      logList: [],
      noMore: false
    });
    this.loadLogs();
  },

  onPullDownRefresh: function() {
    this.setData({
      page: 1,
      logList: [],
      noMore: false
    });
    this.loadUserInfo();
    this.loadStatistics();
    this.loadLogs();
    setTimeout(() => {
      wx.stopPullDownRefresh();
    }, 1000);
  },

  onReachBottom: function() {
    this.loadLogs();
  }
});
