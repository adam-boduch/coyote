import axios from "axios";
import { Tag, Topic } from '../../types/models';

const state = {
  topics: []
};

const mutations = {
  init(state, topics) {
    state.topics = topics;
  },

  mark(state, topic) {
    topic.is_read = true;
  },

  markAll(state) {
    state.topics.forEach(topic => topic.is_read = true);
  },

  subscribe(state, topic) {
    if (topic.is_subscribed) {
      topic.subscribers--;
    }
    else {
      topic.subscribers++;
    }

    topic.is_subscribed = !topic.is_subscribed;
  },

  lock(state, topic: Topic) {
    topic.is_locked = !topic.is_locked;
  },

  toggleTag(state, { topic, tag }: { topic: Topic, tag: Tag }) {
    const index = topic.tags!.findIndex(item => item.name === tag.name);

    index > -1 ? topic.tags!.splice(index, 1) : topic.tags!.push(tag);
  }
};

const actions = {
  mark({ commit }, topic) {
    commit('mark', topic);

    axios.post(`/Forum/Topic/Mark/${topic.id}`);
  },

  markAll({ state, commit }) {
    commit('markAll');

    axios.post(`/Forum/${state.topics[0].forum.slug}/Mark`);
  },

  subscribe({ commit }, topic) {
    commit('subscribe', topic);

    axios.post(`/Forum/Topic/Subscribe/${topic.id}`);
  },

  lock({ commit }, topic) {
    commit('lock', topic);

    axios.post(`/Forum/Topic/Lock/${topic.id}`);
  },

  move({ commit }, { topic, forumId, reasonId }) {
    return axios.post(`/Forum/Topic/Move/${topic.id}`, { id: forumId, reason_id: reasonId });
  },

  changeTitle({ commit }, { topic }) {
    return axios.post(`/Forum/Topic/Subject/${topic.id}`, { subject: topic.subject });
  }
};

export default {
  namespaced: true,
  state,
  mutations,
  actions
};
