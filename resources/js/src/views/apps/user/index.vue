<template>
  <el-tabs type="border-card">
    <el-tab-pane label="User List">
      <user-list />
    </el-tab-pane>
    <el-tab-pane v-if="checkPermission(['assign-field-staff'])" label="Assign Customer Reps">
      <assign-reps-customers />
    </el-tab-pane>
    <!-- <el-tab-pane v-if="checkPermission(['assign-field-staff'])" label="Assign Customer Reps">
      <sales-reps />
    </el-tab-pane> -->
  </el-tabs>
</template>

<script>
import UserList from './UserList.vue';
// import UserList from './UserList.vue';
import AssignRepsCustomers from './AssignRepsCustomers.vue';
import checkPermission from '@/utils/permission';
export default {
  name: 'ManageUsers',
  components: { UserList, AssignRepsCustomers },
  props: {
    canAddNew: {
      type: Boolean,
      default: () => true,
    },
  },
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

