<template>
  <el-tabs type="border-card">
    <el-tab-pane label="All Customers List">
      <customer-list />
    </el-tab-pane>
    <el-tab-pane label="Customer-Team Relationship">
      <customer-team-relationship />
    </el-tab-pane>
    <el-tab-pane label="Reps' Customers List">
      <rep-customers />
    </el-tab-pane>
    <el-tab-pane label="Unique Customers Visit List">
      <unique-customers-visit-list />
    </el-tab-pane>
    <el-tab-pane v-if="checkPermission(['create-customers'])" label="Register New Customer">
      <add-new-customer />
    </el-tab-pane>
    <!-- <el-tab-pane v-if="checkPermission(['assign-field-staff'])" label="Assign Reps to Customers">
      <reassign-customers />
    </el-tab-pane> -->
    <el-tab-pane v-if="checkPermission(['delete-customers'])" label="Handle Duplicates">
      <handle-duplicates />
    </el-tab-pane>
  </el-tabs>
</template>

<script>
import ReassignCustomers from './ReassignCustomers';
import HandleDuplicates from './HandleDuplicates';
import AddNewCustomer from './AddNewCustomer';
import CustomerList from './CustomerList';
import UniqueCustomersVisitList from './UniqueCustomersVisitList';
import CustomerTeamRelationship from './CustomerTeamRelationship';
import RepCustomers from './RepCustomers';
import checkPermission from '@/utils/permission';

export default {
  name: 'Customers',
  components: { ReassignCustomers, HandleDuplicates, CustomerList, CustomerTeamRelationship, RepCustomers, AddNewCustomer, UniqueCustomersVisitList },
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
