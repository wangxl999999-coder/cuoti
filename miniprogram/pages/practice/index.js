const app = getApp();

Page({
  data: {
    subjects: [],
    wrongQuestions: [],
    historyList: [],
    showHistory: false,
    showSubjectPopup: false,
    selectedSubject: 0,
    showPointsAnimation: false,
    earnedPoints: 0,
    pointsAnimation: null
  },

  onLoad: function(options) {
    this.loadSubjects();
  },

  onShow: function() {
    this.loadWrongQuestions();
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

  loadWrongQuestions: function() {
    const that = this;
    app.request({
      url: '/wrong-question/list',
      method: 'GET',
      data: {
        page: 1,
        page_size: 5,
        is_mastered: 0
      },
      success: function(res) {
        if (res.code === 200) {
          that.setData({ wrongQuestions: res.data.list || [] });
        }
      }
    });
  },

  startRandomPractice: function() {
    const that = this;
    wx.showLoading({ title: '加载中...', mask: true });
    
    app.request({
      url: '/question/random',
      method: 'GET',
      data: { count: 5 },
      success: function(res) {
        wx.hideLoading();
        
        if (res.code === 200 && res.data.length > 0) {
          const questions = JSON.stringify(res.data);
          wx.navigateTo({
            url: '/pages/practice/answer?questions=' + encodeURIComponent(questions)
          });
        } else {
          wx.showToast({
            title: '暂无试题，请先添加错题',
            icon: 'none'
          });
        }
      },
      fail: function() {
        wx.hideLoading();
      }
    });
  },

  selectSubjectPractice: function() {
    this.setData({ showSubjectPopup: true });
  },

  closeSubjectPopup: function() {
    this.setData({ showSubjectPopup: false });
  },

  confirmSubjectPractice: function(e) {
    const subjectId = e.currentTarget.dataset.id;
    this.setData({ 
      selectedSubject: subjectId,
      showSubjectPopup: false 
    });
    
    const that = this;
    wx.showLoading({ title: '加载中...', mask: true });
    
    app.request({
      url: '/question/random',
      method: 'GET',
      data: { 
        count: 5,
        subject_id: subjectId || undefined
      },
      success: function(res) {
        wx.hideLoading();
        
        if (res.code === 200 && res.data.length > 0) {
          const questions = JSON.stringify(res.data);
          wx.navigateTo({
            url: '/pages/practice/answer?questions=' + encodeURIComponent(questions)
          });
        } else {
          wx.showToast({
            title: '该科目暂无试题',
            icon: 'none'
          });
        }
      },
      fail: function() {
        wx.hideLoading();
      }
    });
  },

  startPracticeForWrong: function(e) {
    const wrongQuestionId = e.currentTarget.dataset.id;
    const that = this;
    
    wx.showLoading({ title: '生成试题中...', mask: true });
    
    app.request({
      url: '/question/generate',
      method: 'POST',
      data: {
        wrong_question_id: wrongQuestionId,
        count: 3
      },
      success: function(res) {
        wx.hideLoading();
        
        if (res.code === 200 && res.data.length > 0) {
          const questions = JSON.stringify(res.data);
          wx.navigateTo({
            url: '/pages/practice/answer?questions=' + encodeURIComponent(questions)
          });
        } else {
          wx.showToast({
            title: '生成试题失败',
            icon: 'none'
          });
        }
      },
      fail: function() {
        wx.hideLoading();
      }
    });
  },

  goToWrongList: function() {
    wx.switchTab({
      url: '/pages/wrong-question/list'
    });
  },

  preventTouchMove: function() {}
});
