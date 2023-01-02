<template>
  <div class="no-print">
    <el-row :gutter="10">
      <el-col :lg="6" :md="6" :sm="6" :xs="24">
        <label for="">Select Team</label>
        <el-select v-model="form.team_id" filterable style="width: 100%" @change="fetchTeamReps($event)">
          <el-option
            v-for="(team, index) in teams"
            :key="index"
            :label="team.name"
            :value="team.id"

          />
        </el-select>
      </el-col>
      <el-col :lg="6" :md="6" :sm="6" :xs="24">
        <label for="">Select Rep</label>
        <el-select v-model="form.rep_id" filterable style="width: 100%" @change="fetchCustomers($event)">
          <el-option
            v-if="reps.length > 0 && !submitOnRepChange"
            label="All"
            value="all" />
          <el-option
            v-for="(rep, index) in reps"
            :key="index"
            :label="rep.name"
            :value="rep.id"

          />
        </el-select>
        <div v-loading="load_customer" />
      </el-col>
      <el-col v-if="!hideCustomersList" :lg="6" :md="6" :sm="6" :xs="24">
        <label for="">Select Customer</label>
        <el-select v-model="form.customer_id" loading-text filterable style="width: 100%" @change="loadPage()">
          <el-option
            v-if="customers.length > 0"
            label="All"
            value="all" />
          <el-option
            v-for="(customer, index) in customers"
            :key="index"
            :label="customer.business_name"
            :value="customer.id"

          />
        </el-select>
      </el-col>
      <el-col v-if="showDatePicker" :lg="6" :md="6" :sm="6" :xs="24">
        <div v-if="panel !== 'date'">

          <label for="">&nbsp;</label><br>
          <el-popover placement="right" trigger="click">
            <date-range-picker
              :from="$route.query.from"
              :to="$route.query.to"
              :panel="panel"
              :panels="panels"
              :submit-title="submitTitle"
              :future="future"
              @update="setDateRange"
            />
            <el-button id="pick_date1" slot="reference" type="primary">
              <i class="el-icon-date" /> Pick Date Range
            </el-button>
          </el-popover>
        </div>
        <div v-if="panel === 'date'">
          <label for="">Pick Date</label><br>
          <el-date-picker
            v-model="form.date"
            :picker-options="pickerOptions"
            type="date"
            placeholder="Report Date"
            style="width: 100%;"
            format="dd-MM-yyyy"
            value-format="yyyy-MM-dd"
            @input="submitQuery()"
          />
        </div>
      </el-col>
      <el-col v-if="showButton && panel === 'date'" :lg="6" :md="6" :sm="6" :xs="24">
        <label for="">&nbsp;</label><br>
        <el-button type="primary" @click="submitQuery()">Fetch</el-button>
      </el-col>
    </el-row>
  </div>
</template>
<script>
import moment from 'moment';
import Pagination from '@/components/Pagination';
import Resource from '@/api/resource';
export default {
  components: { Pagination },
  props: {
    panel: {
      type: String,
      default: () => 'month',
    },
    panels: {
      type: Array,
      default: () => ['range', 'week', 'month', 'quarter', 'year'],
    },
    hideCustomersList: {
      type: Boolean,
      default: () => false,
    },
    showButton: {
      type: Boolean,
      default: () => false,
    },
    showDatePicker: {
      type: Boolean,
      default: () => true,
    },
    submitOnRepChange: {
      type: Boolean,
      default: () => false,
    },
  },
  data() {
    return {
      pickerOptions: {
        disabledDate(date) {
          var d = new Date(); // today
          d.setDate(d.getDate()); // one year from now
          return date > d;
        },
      },
      teams: [],
      reps: [],
      customers: [],
      load: false,
      total: 0,
      currency: '',
      form: {
        from: '',
        to: '',
        panel: '',
        status: 'pending',
        page: 1,
        limit: 25,
        customer_id: '',
        rep_id: '',
        team_id: '',
        date: '',
      },
      sub_title: '',
      submitTitle: 'Fetch Report',
      future: false,
      show_calendar: false,
      downloadLoading: false,
      load_customer: false,
    };
  },
  created() {
    this.fetchTeams();
    // this.fetchSales();
  },
  methods: {
    moment,
    loadPage() {
      const app = this;
      if (app.form.from !== '') {
        app.submitQuery();
      }
    },
    fetchTeams() {
      const app = this;
      // this.load_table = true;
      const salesRepResource = new Resource('teams');
      salesRepResource
        .list()
        .then((response) => {
          app.teams = response.teams;
          if (app.teams.length > 0) {
            app.form.team_id = app.teams[0].id;
            app.fetchTeamReps(app.form.team_id);
          }
        })
        .catch((error) => {
          console.log(error);
        });
    },
    fetchTeamReps(teamId) {
      const app = this;
      // this.load_table = true;
      app.reps = [];
      app.form.rep_id = '';
      const salesRepResource = new Resource('teams/fetch-reps');
      salesRepResource
        .list({ team_id: teamId })
        .then((response) => {
          app.reps = response.team_reps;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    fetchCustomers(rep_id) {
      const app = this;
      // if (!app.hideCustomersList) {
      app.loadPage();
      app.form.rep_id = rep_id;
      app.form.customer_id = 'all';
      app.load_customer = true;
      const customerResource = new Resource('customers/rep-customers');
      const param = { rep_id, team_id: app.form.team_id };
      customerResource.list(param)
        .then(response => {
          app.customers = response.customers;
          app.load_customer = false;
          if (app.submitOnRepChange) {
            app.submitQuery();
          }
        });
      // }
    },
    // fetchCustomers() {
    //   const app = this;
    //   const customerResource = new Resource('customers/all');
    //   customerResource.list()
    //     .then(response => {
    //       app.customers = response.customers;
    //     });
    // },
    format(date) {
      var month = date.toLocaleString('en-US', { month: 'short' });
      return month + ' ' + date.getDate() + ', ' + date.getFullYear();
    },
    setDateRange(values) {
      const app = this;
      document.getElementById('pick_date1').click();
      app.show_calendar = false;
      let panel = app.panel;
      let from = app.week_start;
      let to = app.week_end;
      if (values !== '') {
        to = this.format(new Date(values.to));
        from = this.format(new Date(values.from));
        panel = values.panel;
      }
      app.form.from = from;
      app.form.to = to;
      app.form.panel = panel;
      app.submitQuery();
    },
    submitQuery() {
      if (this.hideCustomersList) {
        this.form.customers = this.customers;
      }
      this.$emit('submitQuery', this.form);
    },
  },
};
</script>
