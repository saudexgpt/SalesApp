<template>
  <div>
    <el-autocomplete
      v-model="searchString"
      :fetch-suggestions="fetchSuggestions"
      :trigger-on-focus="false"
      class="inline-input no-border my-autocomplete"
      placeholder="Search Invoice Number"
      style="width: 100%;"
      @select="handleSearch"
    >
      <template slot-scope="{ item }">
        <span style="float: left">{{ item }}</span>
      </template>
    </el-autocomplete>
  </div>

</template>

<script>
export default {
  name: 'SearchBox',
  props: {
    invoiceNumbers: {
      type: Array,
      default: () => ([]),
    },
  },
  data() {
    return {
      img: '/images/logo.png',
      showCartContent: false,
      selectedCategory: null,
      categories: [],
      searchString: '',
    };
  },
  methods: {
    handleSearch(invoiceNumber) {
      const app = this;
      app.$emit('selected', invoiceNumber);
    },
    fetchSuggestions(queryString, cb) {
      var items = this.invoiceNumbers;
      var results = queryString ? items.filter(this.createFilter(queryString)) : items;
      // call callback function to return suggestions
      cb(results);
    },
    createFilter(queryString) {
      return (item) => {
        return (item.toLowerCase().indexOf(queryString.toLowerCase()) > -1);
      };
    },
  },
};
</script>
<style lang="scss" scoped>
  .my-autocomplete {
    li {
      line-height: normal;
      padding: 7px;

      .value {
        text-overflow: ellipsis;
        overflow: hidden;
      }
      .link {
        font-size: 12px;
        color: #b4b4b4;
      }
    }
  }
</style>

