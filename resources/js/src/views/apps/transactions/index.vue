<template>
  <el-tabs type="border-card">
    <el-tab-pane label="Product Sales">
      <sales :customers="customers" />
    </el-tab-pane>
    <el-tab-pane label="Collections">
      <payments :customers="customers" />
    </el-tab-pane>
    <el-tab-pane label="Debt">
      <debts :customers="customers" />
    </el-tab-pane>
    <el-tab-pane label="Returned Products">
      <returns :customers="customers" />
    </el-tab-pane>
    <el-tab-pane label="Visits">
      <visits :customers="customers" />
    </el-tab-pane>
  </el-tabs>
</template>
<script>
import Resource from '@/api/resource';
import Sales from './Sales';
import Payments from './Payments';
import Debts from './Debts';
import Returns from './Returns';
import Visits from './Visits';
export default {
  components: { Sales, Payments, Debts, Returns, Visits },
  data() {
    return {
      customers: [],
    };
  },
  created() {
    this.fetchCustomers();
  },
  methods: {
    fetchCustomers() {
      const app = this;
      const customerResource = new Resource('customers/all');
      customerResource.list()
        .then(response => {
          app.customers = response.customers;
        });
    },
  },
};
</script>
