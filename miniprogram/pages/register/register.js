const app = getApp();

Page({
  data: {
    currentStep: 1,
    phone: '',
    nickname: '',
    password: '',
    confirmPassword: '',
    selectedGrade: 0,
    gradeName: '',
    grades: []
  },

  onLoad: function(options) {
    const grades = app.globalData.grades;
    if (grades && grades.length > 0) {
      this.setData({ grades: grades });
    } else {
      this.loadGrades();
    }
  },

  loadGrades: function() {
    const that = this;
    app.request({
      url: '/grade/list',
      method: 'GET',
      success: function(res) {
        if (res.code === 200) {
          that.setData({ grades: res.data });
          app.globalData.grades = res.data;
        }
      }
    });
  },

  onPhoneInput: function(e) {
    this.setData({ phone: e.detail.value });
  },

  onNicknameInput: function(e) {
    this.setData({ nickname: e.detail.value });
  },

  onPasswordInput: function(e) {
    this.setData({ password: e.detail.value });
  },

  onConfirmPasswordInput: function(e) {
    this.setData({ confirmPassword: e.detail.value });
  },

  goToStep2: function() {
    const { phone, password, confirmPassword } = this.data;
    
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
    
    if (!password || password.length < 6) {
      wx.showToast({
        title: '密码至少6位',
        icon: 'none'
      });
      return;
    }
    
    if (password !== confirmPassword) {
      wx.showToast({
        title: '两次密码不一致',
        icon: 'none'
      });
      return;
    }
    
    this.setData({ currentStep: 2 });
  },

  goToStep1: function() {
    this.setData({ currentStep: 1 });
  },

  selectGrade: function(e) {
    const id = e.currentTarget.dataset.id;
    const grade = this.data.grades.find(g => g.id === id);
    this.setData({
      selectedGrade: id,
      gradeName: grade ? grade.name : ''
    });
  },

  doRegister: function() {
    const { phone, nickname, password, selectedGrade } = this.data;
    
    if (!selectedGrade) {
      wx.showToast({
        title: '请选择年级',
        icon: 'none'
      });
      return;
    }
    
    const openid = wx.getStorageSync('temp_openid') || '';
    
    const that = this;
    wx.showLoading({
      title: '注册中...',
      mask: true
    });
    
    app.request({
      url: '/user/register',
      method: 'POST',
      data: {
        phone: phone,
        password: password,
        grade_id: selectedGrade,
        nickname: nickname,
        openid: openid
      },
      success: function(res) {
        wx.hideLoading();
        
        if (res.code === 200) {
          app.globalData.token = res.data.token;
          app.globalData.userInfo = res.data.user;
          wx.setStorageSync('token', res.data.token);
          wx.setStorageSync('userInfo', res.data.user);
          wx.removeStorageSync('temp_openid');
          
          that.setData({ 
            currentStep: 3,
            gradeName: res.data.user.grade_name
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
        wx.showToast({
          title: '注册失败',
          icon: 'none'
        });
      }
    });
  },

  goToHome: function() {
    wx.switchTab({
      url: '/pages/index/index'
    });
  }
});
