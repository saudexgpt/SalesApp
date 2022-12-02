import Resource from '@/api/resource';

const state = {
  customers: [],
};

const mutations = {
  SET_CUSTOMERS: (state, customers) => {
    state.customers = customers;
  },
  ADD_NEW_CUSTOMER: (state, customer) => {
    state.customers.unshift(customer);
  },
};

const actions = {
  addNewNotifications({ commit }, customer) {
    commit('ADD_NEW_CUSTOMER', customer);
  },
  fetch({ commit }) {
    return new Promise((resolve, reject) => {
      const customerResource = new Resource('customers/all');
      customerResource.list()
        .then(response => {
          commit('SET_CUSTOMERS', response.customers);
          resolve();
        })
        .catch(error => {
          reject(error);
        });
    });
  },
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
};
