<template>
  <el-tabs type="border-card">
    <el-tab-pane label="Sales">
      <sales :customers="customers" />
    </el-tab-pane>
    <el-tab-pane label="Payments">
      <payments :customers="customers" />
    </el-tab-pane>
    <el-tab-pane label="Debt">
      <debts :customers="customers" />
    </el-tab-pane>
  </el-tabs>
</template>
<script>
import Resource from '@/api/resource';
import Sales from './Sales';
import Payments from './Payments';
import Debts from './Debts';
export default {
  components: { Sales, Payments, Debts },
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
