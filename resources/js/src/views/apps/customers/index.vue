<template>
  <el-tabs type="border-card">
    <el-tab-pane label="Customer List">
      <customer-list />
    </el-tab-pane>
    <el-tab-pane v-if="checkPermission(['assign-field-staff'])" label="Assign Reps to Customers">
      <reassign-customers />
    </el-tab-pane>
  </el-tabs>
</template>

<script>
import ReassignCustomers from './ReassignCustomers';
import CustomerList from './CustomerList';
import checkPermission from '@/utils/permission';

export default {
  name: 'Customers',
  components: { ReassignCustomers, CustomerList },
  data() {
    return {

    };
  },
  created() {
  },
  methods: {
    checkPermission,
  },
};
</script>
