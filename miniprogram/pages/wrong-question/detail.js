const app = getApp();

Page({
  data: {
    questionId: 0,
    question: null,
    relatedQuestions: [],
    showPointsAnimation: false,
    pointsAnimation: null
  },

  onLoad: function(options) {
    if (options.id) {
      this.setData({ questionId: parseInt(options.id) });
      this.loadQuestionDetail();
    }
  },

  loadQuestionDetail: function() {
    const that = this;
    const { questionId } = this.data;
    
    wx.showLoading({ title: '加载中...', mask: true });
    
    app.request({
      url: '/wrong-question/detail',
      method: 'GET',
      data: { id: questionId },
      success: function(res) {
        wx.hideLoading();
        
        if (res.code === 200) {
          that.setData({ question: res.data });
          that.loadRelatedQuestions();
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

  loadRelatedQuestions: function() {
    const that = this;
    const { questionId } = this.data;
    
    app.request({
      url: '/question/generate',
      method: 'POST',
      data: {
        wrong_question_id: questionId,
        count: 3
      },
      success: function(res) {
        if (res.code === 200) {
          that.setData({ relatedQuestions: res.data.slice(0, 3) });
        }
      }
    });
  },

  previewImage: function() {
    const { question } = this.data;
    if (question && question.image_url) {
      wx.previewImage({
        urls: [question.image_url]
      });
    }
  },

  toggleMastered: function() {
    const { question } = this.data;
    const that = this;
    
    wx.showLoading({ title: '处理中...', mask: true });
    
    app.request({
      url: '/wrong-question/mastered',
      method: 'POST',
      data: {
        id: question.id,
        is_mastered: question.is_mastered ? 0 : 1
      },
      success: function(res) {
        wx.hideLoading();
        
        if (res.code === 200) {
          that.setData({
            'question.is_mastered': res.data.is_mastered
          });
          
          if (res.data.is_mastered) {
            that.showMasteredAnimation();
          }
          
          wx.showToast({
            title: res.data.is_mastered ? '已标记为掌握' : '已取消掌握标记',
            icon: 'success'
          });
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

  showMasteredAnimation: function() {
    const that = this;
    this.setData({ showPointsAnimation: true });
    
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
    
    setTimeout(() => {
      that.setData({ showPointsAnimation: false });
    }, 2000);
  },

  startPractice: function() {
    const { questionId } = this.data;
    
    wx.showLoading({ title: '生成试题中...', mask: true });
    
    const that = this;
    app.request({
      url: '/question/generate',
      method: 'POST',
      data: {
        wrong_question_id: questionId,
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

  goToPractice: function(e) {
    const id = e.currentTarget.dataset.id;
    const { relatedQuestions } = this.data;
    const question = relatedQuestions.find(q => q.id === id);
    
    if (question) {
      const questions = JSON.stringify([question]);
      wx.navigateTo({
        url: '/pages/practice/answer?questions=' + encodeURIComponent(questions)
      });
    }
  }
});
