const app = getApp();

Page({
  data: {
    collections: [],
    totalCollections: 0,
    totalQuestions: 0,
    page: 1,
    pageSize: 10,
    loading: false,
    noMore: false,
    showCreatePopup: false,
    newCollection: {
      name: '',
      description: '',
      subject_id: 0
    },
    subjectId: 0,
    subjects: []
  },

  onLoad: function(options) {
    this.loadSubjects();
    this.loadCollections();
  },

  onShow: function() {
    this.setData({ page: 1, collections: [], noMore: false });
    this.loadCollections();
  },

  loadSubjects: function() {
    const that = this;
    app.request({
      url: '/subject/list',
      method: 'GET',
      success: function(res) {
        if (res.code === 200) {
          that.setData({ subjects: res.data });
        }
      }
    });
  },

  loadCollections: function() {
    if (this.data.loading || this.data.noMore) return;
    
    this.setData({ loading: true });
    
    const that = this;
    const { page, pageSize } = this.data;
    
    app.request({
      url: '/collection/list',
      method: 'GET',
      data: {
        page: page,
        page_size: pageSize
      },
      success: function(res) {
        if (res.code === 200) {
          const newList = res.data.list || [];
          const total = res.data.total || 0;
          
          if (newList.length > 0) {
            let totalQuestions = 0;
            newList.forEach(item => {
              totalQuestions += item.question_count || 0;
            });
            
            that.setData({
              collections: that.data.collections.concat(newList),
              totalCollections: total,
              totalQuestions: totalQuestions,
              loading: false,
              noMore: newList.length < pageSize || that.data.collections.length + newList.length >= total,
              page: that.data.page + 1
            });
          } else {
            that.setData({
              loading: false,
              noMore: true
            });
          }
        }
      },
      fail: function() {
        that.setData({ loading: false });
      }
    });
  },

  createCollection: function() {
    this.setData({
      showCreatePopup: true,
      newCollection: {
        name: '',
        description: '',
        subject_id: 0
      },
      subjectId: 0
    });
  },

  closePopup: function() {
    this.setData({ showCreatePopup: false });
  },

  onNameInput: function(e) {
    this.setData({
      'newCollection.name': e.detail.value
    });
  },

  onDescInput: function(e) {
    this.setData({
      'newCollection.description': e.detail.value
    });
  },

  selectSubject: function(e) {
    const id = e.currentTarget.dataset.id;
    this.setData({
      subjectId: id,
      'newCollection.subject_id': id
    });
  },

  submitCreate: function() {
    const { newCollection } = this.data;
    
    if (!newCollection.name.trim()) {
      wx.showToast({
        title: '请输入错题本名称',
        icon: 'none'
      });
      return;
    }
    
    const that = this;
    wx.showLoading({ title: '创建中...', mask: true });
    
    app.request({
      url: '/collection/create',
      method: 'POST',
      data: {
        name: newCollection.name.trim(),
        description: newCollection.description,
        subject_id: newCollection.subject_id
      },
      success: function(res) {
        wx.hideLoading();
        
        if (res.code === 200) {
          wx.showToast({
            title: '创建成功',
            icon: 'success'
          });
          
          that.setData({
            showCreatePopup: false,
            page: 1,
            collections: [],
            noMore: false
          });
          
          setTimeout(() => {
            that.loadCollections();
          }, 500);
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

  goToDetail: function(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({
      url: '/pages/collection/detail?id=' + id
    });
  },

  onPullDownRefresh: function() {
    this.setData({
      page: 1,
      collections: [],
      noMore: false
    });
    this.loadCollections();
    setTimeout(() => {
      wx.stopPullDownRefresh();
    }, 1000);
  },

  onReachBottom: function() {
    this.loadCollections();
  },

  preventMove: function() {}
});
