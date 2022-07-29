<template>
  <div v-if="dashboardData !== null " class="vx-row">

    <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
      <statistics-card-line
        :statistic="dashboardData.customers.length"
        hide-chart
        class="mb-base"
        icon="UsersIcon"
        icon-right
        statistic-title="No. of Customers"
        color="warning" />
    </div>

    <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
      <statistics-card-line
        :statistic="dashboardData.currency + dashboardData.sales.toLocaleString()"
        :statistic-title="dashboardData. month + ' Sales'"
        hide-chart
        class="mb-base"
        icon="ShoppingBagIcon"
        icon-right
        color="dark" />
    </div>

    <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
      <statistics-card-line
        :statistic="dashboardData.currency + dashboardData.collections.toLocaleString()"
        :statistic-title="dashboardData. month + ' Collections'"
        hide-chart
        class="mb-base"
        icon="CreditCardIcon"
        icon-right
        color="success" />
    </div>

    <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
      <statistics-card-line
        :statistic="dashboardData.currency + dashboardData.debt.toLocaleString()"
        hide-chart
        class="mb-base"
        icon="CreditCardIcon"
        icon-right
        statistic-title="Total Debt"
        color="danger" />
    </div>
  </div>
</template>

<script>
import StatisticsCardLine from '@/components/statistics-cards/StatisticsCardLine.vue';
export default {
  name: 'DataAnalysis',
  components: { StatisticsCardLine },
  props: {
    dashboardData: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
    };
  },
};
</script>
