import axios from "axios";

const state = {
  messages: null, // initial value must be null to show fa-spinner
  count: 0
};

const getters = {
  isEmpty: state => state.messages === null
};

const mutations = {
  init(state, count) {
    state.count = count;
  },

  set(state, messages) {
    state.messages = messages;
  },

  reset(state) {
    state.messages = null;
  }
};

const actions = {
  get({ commit }) {
    return axios.get('/User/Pm/Inbox').then(result => commit('set', result.data));
  }
};

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
};
