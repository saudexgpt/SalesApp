<template>
  <el-tabs>
    <el-tab-pane label="Hospital Visits">
      <hospital-visits :customers="customers" />
    </el-tab-pane>
    <el-tab-pane label="General Visits">
      <general-visits :customers="customers" />
    </el-tab-pane>
  </el-tabs>
</template>
<script>
import HospitalVisits from './partials/HospitalVisits';
import GeneralVisits from './partials/GeneralVisits';
export default {
  components: { HospitalVisits, GeneralVisits },
  props: {
    customers: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
    };
  },
};
</script>
