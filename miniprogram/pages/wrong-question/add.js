const app = getApp();

Page({
  data: {
    imagePath: '',
    showForm: false,
    subjects: [],
    subjectIndex: 0,
    questionText: '',
    answerText: '',
    analysis: '',
    knowledgePoints: '',
    difficulty: 2,
    source: '',
    analyzing: false,
    showPointsAnimation: false,
    pointsAnimation: null,
    questionId: 0
  },

  onLoad: function(options) {
    this.loadSubjects();
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

  chooseImage: function() {
    const that = this;
    wx.chooseMedia({
      count: 1,
      mediaType: ['image'],
      sourceType: ['camera', 'album'],
      success: function(res) {
        const tempFilePath = res.tempFiles[0].tempFilePath;
        that.setData({
          imagePath: tempFilePath,
          showForm: false
        });
      }
    });
  },

  confirmPhoto: function() {
    const that = this;
    this.setData({ analyzing: true });
    
    app.uploadFile({
      url: '/wrong-question/upload',
      filePath: that.data.imagePath,
      name: 'image',
      formData: {
        subject_id: that.data.subjects[that.data.subjectIndex]?.id || 0
      },
      success: function(res) {
        that.setData({ analyzing: false });
        
        if (res.code === 200) {
          that.setData({
            showForm: true,
            questionId: res.data.id,
            questionText: res.data.question_text || '',
            answerText: res.data.answer_text || '',
            analysis: res.data.analysis || '',
            knowledgePoints: res.data.knowledge_points || ''
          });
          
          if (res.data.question_text) {
            wx.showToast({
              title: 'AI识别成功',
              icon: 'success'
            });
          }
        } else {
          that.setData({ showForm: true });
          wx.showToast({
            title: res.msg,
            icon: 'none'
          });
        }
      },
      fail: function() {
        that.setData({
          analyzing: false,
          showForm: true
        });
      }
    });
  },

  onSubjectChange: function(e) {
    this.setData({ subjectIndex: parseInt(e.detail.value) });
  },

  onQuestionInput: function(e) {
    this.setData({ questionText: e.detail.value });
  },

  onAnswerInput: function(e) {
    this.setData({ answerText: e.detail.value });
  },

  onAnalysisInput: function(e) {
    this.setData({ analysis: e.detail.value });
  },

  onKnowledgeInput: function(e) {
    this.setData({ knowledgePoints: e.detail.value });
  },

  onSourceInput: function(e) {
    this.setData({ source: e.detail.value });
  },

  selectDifficulty: function(e) {
    const level = e.currentTarget.dataset.level;
    this.setData({ difficulty: parseInt(level) });
  },

  submitQuestion: function() {
    if (this.data.questionId > 0) {
      this.updateQuestion();
    } else {
      this.uploadAndSave();
    }
  },

  uploadAndSave: function() {
    if (!this.data.imagePath) {
      wx.showToast({
        title: '请上传错题图片',
        icon: 'none'
      });
      return;
    }
    
    if (!this.data.questionText && !this.data.questionId) {
      wx.showToast({
        title: '请输入题目内容',
        icon: 'none'
      });
      return;
    }
    
    const that = this;
    const subjectId = this.data.subjects[this.data.subjectIndex]?.id || 0;
    
    app.uploadFile({
      url: '/wrong-question/upload',
      filePath: this.data.imagePath,
      name: 'image',
      formData: {
        subject_id: subjectId,
        question_text: this.data.questionText,
        answer_text: this.data.answerText,
        analysis: this.data.analysis,
        knowledge_points: this.data.knowledgePoints,
        difficulty: this.data.difficulty,
        source: this.data.source
      },
      success: function(res) {
        if (res.code === 200) {
          that.showSuccessAnimation();
        } else {
          wx.showToast({
            title: res.msg,
            icon: 'none'
          });
        }
      }
    });
  },

  updateQuestion: function() {
    if (!this.data.questionText) {
      wx.showToast({
        title: '请输入题目内容',
        icon: 'none'
      });
      return;
    }
    
    const that = this;
    const subjectId = this.data.subjects[this.data.subjectIndex]?.id || 0;
    
    app.request({
      url: '/wrong-question/update',
      method: 'POST',
      data: {
        id: this.data.questionId,
        subject_id: subjectId,
        question_text: this.data.questionText,
        answer_text: this.data.answerText,
        analysis: this.data.analysis,
        knowledge_points: this.data.knowledgePoints,
        difficulty: this.data.difficulty,
        source: this.data.source
      },
      success: function(res) {
        if (res.code === 200) {
          that.showSuccessAnimation();
        } else {
          wx.showToast({
            title: res.msg,
            icon: 'none'
          });
        }
      }
    });
  },

  submitAndPractice: function() {
    const that = this;
    
    const originalSubmit = this.data.questionId > 0 ? this.updateQuestion.bind(this) : this.uploadAndSave.bind(this);
    
    originalSubmit();
    
    if (this.data.questionId > 0) {
      setTimeout(() => {
        wx.navigateTo({
          url: '/pages/practice/answer?wrong_question_id=' + that.data.questionId
        });
      }, 2000);
    }
  },

  showSuccessAnimation: function() {
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
      wx.showToast({
        title: '保存成功',
        icon: 'success'
      });
      setTimeout(() => {
        wx.navigateBack();
      }, 1500);
    }, 2000);
  }
});
