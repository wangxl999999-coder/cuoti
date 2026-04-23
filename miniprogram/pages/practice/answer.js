const app = getApp();

let _timerId = null;

Page({
  data: {
    questions: [],
    currentIndex: 0,
    currentQuestion: null,
    selectedOption: '',
    userAnswer: '',
    showResult: false,
    isCorrect: false,
    earnedPoints: 0,
    usedTime: 0,
    isTimerRunning: false,
    showPointsAnimation: false,
    pointsAnimation: null,
    showCoinAnimation: false,
    coinCount: 5,
    typeText: ''
  },

  _clearTimer: function() {
    if (_timerId) {
      clearInterval(_timerId);
      _timerId = null;
    }
    this.setData({ isTimerRunning: false });
  },

  onLoad: function(options) {
    if (options.questions) {
      try {
        const questions = JSON.parse(decodeURIComponent(options.questions));
        this.setData({ questions: questions });
        this.initCurrentQuestion();
        this.startTimer();
      } catch (e) {
        console.error('解析试题失败', e);
        wx.showToast({
          title: '加载试题失败',
          icon: 'none'
        });
      }
    } else if (options.wrong_question_id) {
      this.loadQuestionsForWrong(options.wrong_question_id);
    }
  },

  onShow: function() {
    if (this.data.questions && this.data.questions.length > 0 && !this.data.isTimerRunning) {
      this.startTimer();
    }
  },

  onHide: function() {
    this._clearTimer();
  },

  onUnload: function() {
    this._clearTimer();
  },

  loadQuestionsForWrong: function(wrongQuestionId) {
    const that = this;
    wx.showLoading({ title: '加载中...', mask: true });
    
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
          that.setData({ questions: res.data });
          that.initCurrentQuestion();
          that.startTimer();
        } else {
          wx.showToast({
            title: '生成试题失败',
            icon: 'none'
          });
          setTimeout(() => {
            wx.navigateBack();
          }, 1500);
        }
      },
      fail: function() {
        wx.hideLoading();
        wx.showToast({
          title: '加载失败',
          icon: 'none'
        });
      }
    });
  },

  initCurrentQuestion: function() {
    const { questions, currentIndex } = this.data;
    const currentQuestion = questions[currentIndex];
    
    let typeText = '单选题';
    if (currentQuestion.question_type === 2) {
      typeText = '多选题';
    } else if (currentQuestion.question_type === 3) {
      typeText = '填空题';
    } else if (currentQuestion.question_type === 4) {
      typeText = '解答题';
    }
    
    this.setData({
      currentQuestion: currentQuestion,
      selectedOption: '',
      userAnswer: '',
      showResult: false,
      isCorrect: false,
      earnedPoints: 0,
      typeText: typeText
    });
  },

  startTimer: function() {
    this._clearTimer();
    
    const that = this;
    _timerId = setInterval(() => {
      that.setData({
        usedTime: that.data.usedTime + 1
      });
    }, 1000);
    
    this.setData({ 
      usedTime: 0,
      isTimerRunning: true
    });
  },

  formatTime: function(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  },

  selectOption: function(e) {
    if (this.data.showResult) return;
    
    const option = e.currentTarget.dataset.option;
    this.setData({ selectedOption: option });
  },

  onAnswerInput: function(e) {
    this.setData({ userAnswer: e.detail.value });
  },

  submitAnswer: function() {
    const { currentQuestion, selectedOption, userAnswer, usedTime, questions, currentIndex } = this.data;
    
    let answer = selectedOption;
    if (currentQuestion.question_type >= 3) {
      answer = userAnswer;
    }
    
    if (!answer && currentQuestion.question_type <= 2) {
      wx.showToast({
        title: '请选择答案',
        icon: 'none'
      });
      return;
    }
    
    if (!answer && currentQuestion.question_type >= 3) {
      wx.showToast({
        title: '请输入答案',
        icon: 'none'
      });
      return;
    }
    
    const that = this;
    wx.showLoading({ title: '提交中...', mask: true });
    
    app.request({
      url: '/question/submit',
      method: 'POST',
      data: {
        question_id: currentQuestion.id,
        user_answer: answer,
        used_time: usedTime
      },
      success: function(res) {
        wx.hideLoading();
        
        if (res.code === 200) {
          that.setData({
            showResult: true,
            isCorrect: res.data.is_correct,
            earnedPoints: res.data.points_earned || 0
          });
          
          if (res.data.is_correct && res.data.points_earned > 0) {
            that.showRewardAnimation(res.data.points_earned);
          }
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

  showRewardAnimation: function(points) {
    const that = this;
    this.setData({
      showPointsAnimation: true,
      showCoinAnimation: true
    });
    
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
      that.setData({
        showPointsAnimation: false,
        showCoinAnimation: false
      });
    }, 2000);
  },

  prevQuestion: function() {
    if (this.data.currentIndex > 0) {
      this.setData({
        currentIndex: this.data.currentIndex - 1
      });
      this.initCurrentQuestion();
    }
  },

  nextQuestion: function() {
    if (this.data.currentIndex < this.data.questions.length - 1) {
      this.setData({
        currentIndex: this.data.currentIndex + 1
      });
      this.initCurrentQuestion();
    }
  },

  finishPractice: function() {
    wx.showModal({
      title: '完成练习',
      content: '确定要结束本次练习吗？',
      success: function(res) {
        if (res.confirm) {
          wx.navigateBack();
        }
      }
    });
  }
});
