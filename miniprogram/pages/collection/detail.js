const app = getApp();

Page({
  data: {
    collectionId: 0,
    collection: null,
    questions: [],
    masteredCount: 0,
    notMasteredCount: 0,
    page: 1,
    pageSize: 10,
    loading: false,
    noMore: false,
    currentFilter: 'all',
    showAddPopup: false,
    searchKeyword: '',
    availableQuestions: [],
    selectedIds: []
  },

  onLoad: function(options) {
    if (options.id) {
      this.setData({ collectionId: parseInt(options.id) });
      this.loadCollectionDetail();
      this.loadQuestions();
    }
  },

  loadCollectionDetail: function() {
    const that = this;
    const { collectionId } = this.data;
    
    app.request({
      url: '/collection/detail',
      method: 'GET',
      data: { id: collectionId },
      success: function(res) {
        if (res.code === 200) {
          wx.setNavigationBarTitle({
            title: res.data.name
          });
          that.setData({ collection: res.data });
        }
      }
    });
  },

  loadQuestions: function() {
    if (this.data.loading || this.data.noMore) return;
    
    this.setData({ loading: true });
    
    const that = this;
    const { collectionId, page, pageSize, currentFilter } = this.data;
    
    const params = {
      id: collectionId,
      page: page,
      page_size: pageSize
    };
    
    if (currentFilter === 'mastered') {
      params.is_mastered = 1;
    } else if (currentFilter === 'not_mastered') {
      params.is_mastered = 0;
    }
    
    app.request({
      url: '/collection/questions',
      method: 'GET',
      data: params,
      success: function(res) {
        if (res.code === 200) {
          const newList = res.data.list || [];
          const total = res.data.total || 0;
          
          let mastered = 0;
          let notMastered = 0;
          newList.forEach(item => {
            if (item.is_mastered) {
              mastered++;
            } else {
              notMastered++;
            }
          });
          
          that.setData({
            questions: that.data.questions.concat(newList),
            masteredCount: that.data.masteredCount + mastered,
            notMasteredCount: that.data.notMasteredCount + notMastered,
            loading: false,
            noMore: newList.length < pageSize || that.data.questions.length + newList.length >= total,
            page: that.data.page + 1
          });
        }
      },
      fail: function() {
        that.setData({ loading: false });
      }
    });
  },

  filterQuestions: function(e) {
    const filter = e.currentTarget.dataset.filter;
    if (filter === this.data.currentFilter) return;
    
    this.setData({
      currentFilter: filter,
      page: 1,
      questions: [],
      masteredCount: 0,
      notMasteredCount: 0,
      noMore: false
    });
    
    this.loadQuestions();
  },

  startPracticeAll: function() {
    const { questions, collectionId } = this.data;
    
    if (questions.length === 0) {
      wx.showToast({
        title: '暂无可练习的题目',
        icon: 'none'
      });
      return;
    }
    
    wx.showModal({
      title: '确认练习',
      content: `本错题本共有${questions.length}道题，是否开始练习？`,
      success: function(res) {
        if (res.confirm) {
          const wrongQuestionIds = questions.map(q => q.wrong_question_id).slice(0, 5);
          
          wx.showLoading({ title: '生成试题中...', mask: true });
          
          app.request({
            url: '/question/generate-multiple',
            method: 'POST',
            data: {
              wrong_question_ids: wrongQuestionIds
            },
            success: function(res) {
              wx.hideLoading();
              
              if (res.code === 200 && res.data.length > 0) {
                const questionsData = JSON.stringify(res.data);
                wx.navigateTo({
                  url: '/pages/practice/answer?questions=' + encodeURIComponent(questionsData)
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
        }
      }
    });
  },

  showAddQuestion: function() {
    this.setData({
      showAddPopup: true,
      searchKeyword: '',
      availableQuestions: [],
      selectedIds: []
    });
    this.loadAvailableQuestions();
  },

  closeAddPopup: function() {
    this.setData({ showAddPopup: false });
  },

  updateSelectedState: function() {
    const { availableQuestions, selectedIds } = this.data;
    const updatedQuestions = availableQuestions.map(item => {
      return {
        ...item,
        _selected: selectedIds.indexOf(item.id) > -1
      };
    });
    this.setData({ availableQuestions: updatedQuestions });
  },

  loadAvailableQuestions: function() {
    const that = this;
    const { collectionId } = this.data;
    
    app.request({
      url: '/wrong-question/list',
      method: 'GET',
      data: {
        page_size: 20,
        exclude_collection_id: collectionId
      },
      success: function(res) {
        if (res.code === 200) {
          that.setData({ availableQuestions: res.data.list || [] });
          that.updateSelectedState();
        }
      }
    });
  },

  onSearchInput: function(e) {
    this.setData({ searchKeyword: e.detail.value });
  },

  toggleSelect: function(e) {
    const id = e.currentTarget.dataset.id;
    const { selectedIds } = this.data;
    
    const index = selectedIds.indexOf(id);
    if (index > -1) {
      selectedIds.splice(index, 1);
    } else {
      selectedIds.push(id);
    }
    
    this.setData({ selectedIds: [...selectedIds] });
    this.updateSelectedState();
  },

  submitAdd: function() {
    const { selectedIds, collectionId } = this.data;
    
    if (selectedIds.length === 0) {
      wx.showToast({
        title: '请选择要添加的错题',
        icon: 'none'
      });
      return;
    }
    
    const that = this;
    wx.showLoading({ title: '添加中...', mask: true });
    
    app.request({
      url: '/collection/add-questions',
      method: 'POST',
      data: {
        id: collectionId,
        wrong_question_ids: selectedIds
      },
      success: function(res) {
        wx.hideLoading();
        
        if (res.code === 200) {
          wx.showToast({
            title: '添加成功',
            icon: 'success'
          });
          
          that.setData({
            showAddPopup: false,
            page: 1,
            questions: [],
            masteredCount: 0,
            notMasteredCount: 0,
            noMore: false
          });
          
          setTimeout(() => {
            that.loadQuestions();
            that.loadCollectionDetail();
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

  goToQuestion: function(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({
      url: '/pages/wrong-question/detail?id=' + id
    });
  },

  removeQuestion: function(e) {
    const id = e.currentTarget.dataset.id;
    const that = this;
    
    wx.showModal({
      title: '提示',
      content: '确定要从错题本中移除这道题吗？',
      success: function(res) {
        if (res.confirm) {
          wx.showLoading({ title: '处理中...', mask: true });
          
          app.request({
            url: '/collection/remove-question',
            method: 'POST',
            data: {
              id: that.data.collectionId,
              question_id: id
            },
            success: function(res) {
              wx.hideLoading();
              
              if (res.code === 200) {
                wx.showToast({
                  title: '移除成功',
                  icon: 'success'
                });
                
                const questions = that.data.questions.filter(q => q.id !== id);
                that.setData({ questions: questions });
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
        }
      }
    });
  },

  stopPropagation: function() {},

  onPullDownRefresh: function() {
    this.setData({
      page: 1,
      questions: [],
      masteredCount: 0,
      notMasteredCount: 0,
      noMore: false
    });
    this.loadCollectionDetail();
    this.loadQuestions();
    setTimeout(() => {
      wx.stopPullDownRefresh();
    }, 1000);
  },

  onReachBottom: function() {
    this.loadQuestions();
  }
});
