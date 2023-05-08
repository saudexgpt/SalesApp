import Resource from '@/api/resource';

const state = {
  products: [],
};

const mutations = {
  SET_PRODUCTS: (state, products) => {
    state.products = products;
  },
};

const actions = {
  fetch({ commit }) {
    return new Promise((resolve, reject) => {
      const productResource = new Resource('products');
      productResource.list()
        .then(response => {
          commit('SET_PRODUCTS', response.items);
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
