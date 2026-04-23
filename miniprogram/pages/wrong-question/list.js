const app = getApp();

Page({
  data: {
    subjects: [],
    currentSubject: 0,
    currentStatus: -1,
    questionList: [],
    page: 1,
    pageSize: 10,
    loading: false,
    noMore: false
  },

  onLoad: function(options) {
    this.loadSubjects();
  },

  onShow: function() {
    this.refreshList();
  },

  loadSubjects: function() {
    const subjects = app.globalData.subjects;
    if (subjects && subjects.length > 0) {
      this.setData({ subjects: subjects });
    } else {
      const that = this;
      app.request({
        url: '/subject/list',
        method: 'GET',
        success: function(res) {
          if (res.code === 200) {
            that.setData({ subjects: res.data });
            app.globalData.subjects = res.data;
          }
        }
      });
    }
  },

  refreshList: function() {
    this.setData({
      page: 1,
      questionList: [],
      noMore: false
    });
    this.loadQuestions();
  },

  loadQuestions: function() {
    if (this.data.loading || this.data.noMore) return;
    
    this.setData({ loading: true });
    
    const that = this;
    const { page, pageSize, currentSubject, currentStatus } = this.data;
    
    const params = {
      page: page,
      page_size: pageSize
    };
    
    if (currentSubject > 0) {
      params.subject_id = currentSubject;
    }
    
    if (currentStatus >= 0) {
      params.is_mastered = currentStatus;
    }
    
    app.request({
      url: '/wrong-question/list',
      method: 'GET',
      data: params,
      success: function(res) {
        if (res.code === 200) {
          const newList = res.data.list || [];
          const total = res.data.total || 0;
          
          that.setData({
            questionList: that.data.questionList.concat(newList),
            loading: false,
            noMore: newList.length < pageSize || that.data.questionList.length + newList.length >= total,
            page: that.data.page + 1
          });
        }
      },
      fail: function() {
        that.setData({ loading: false });
      }
    });
  },

  selectSubject: function(e) {
    const id = e.currentTarget.dataset.id;
    this.setData({
      currentSubject: id,
      page: 1,
      questionList: [],
      noMore: false
    });
    this.loadQuestions();
  },

  selectStatus: function(e) {
    const status = e.currentTarget.dataset.status;
    this.setData({
      currentStatus: status,
      page: 1,
      questionList: [],
      noMore: false
    });
    this.loadQuestions();
  },

  goToDetail: function(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({
      url: '/pages/wrong-question/detail?id=' + id
    });
  },

  goToAdd: function() {
    wx.navigateTo({
      url: '/pages/wrong-question/add'
    });
  },

  onPullDownRefresh: function() {
    this.refreshList();
    setTimeout(() => {
      wx.stopPullDownRefresh();
    }, 1000);
  },

  onReachBottom: function() {
    this.loadQuestions();
  }
});
