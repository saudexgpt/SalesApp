<template>
  <vx-card>
    <div class="vx-row">
      <div class="vx-col lg:w-3/4 w-full">
        <div class="flex items-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
          <span class="font-medium text-lg">Add Schedule</span>
        </div>
        <vs-divider />
      </div>
    </div>
    <el-row :gutter="10">
      <el-col :lg="8" :md="8" :sm="8" :xs="24" style="background: #fcfcfc; padding: 10px;">
        <label for="">Pick Schedule Date</label>
        <el-date-picker
          v-model="form.schedule_date"
          :picker-options="pickerOptions"
          type="date"
          placeholder="Pick a Date"
          format="yyyy-MM-dd"
          value-format="yyyy-MM-dd"
          style="width: 100%"
        />
        <p />
        <label for="">Select Rep</label>
        <el-select
          v-model="selected_rep"
          value-key="id"
          filterable
          style="width: 100%"
          @change="fetchCustomers($event.id)">
          <el-option
            v-for="(rep, index) in reps"
            :key="index"
            :label="rep.name"
            :value="rep"

          />
        </el-select>
        <label for="">Repeat Schedule (Make Routine)</label>
        <el-switch
          v-model="form.repeat_schedule"
          style="display: block"
          active-color="#13ce66"
          inactive-color="#ff4949"
          active-text="yes"
          inactive-text="no"
        />
        <hr>
        <el-button :disabled="form.customer_ids.length < 1" type="primary" round @click="submitSchedule()">
          Submit Schedule
        </el-button>
        <!-- <el-select
          v-model="form.customer_ids"
          value-key="id"
          filterable
          multiple
          collapse-tags
          style="width: 100%">
          <el-option
            v-for="(customer, index) in customers"
            :key="index"
            :label="customer.business_name"
            :value="customer"

          />
        </el-select> -->
      </el-col>
      <el-col v-loading="load_customer" :lg="16" :md="16" :sm="16" :xs="24">
        <el-alert v-if="selected_rep !== ''" :closable="false" type="success"><strong>Pick the customers to schedule for {{ selected_rep.name }}</strong></el-alert>
        <v-client-table
          v-model="customers"
          :columns="[
            'select',
            'business_name',
            'address',
          ]"
          :options="options"
        >
          <template slot="business_name" slot-scope="scope">
            <strong>{{ scope.row.business_name }}</strong><br>
            <small><strong>Coordinate: </strong>{{ scope.row.longitude }}, {{ scope.row.latitude }}</small>
          </template>
          <template slot="select" slot-scope="scope">
            <el-checkbox v-model="form.customer_ids" :label="scope.row.id" border/>
          </template>
        </v-client-table>
        <!-- <el-transfer
          :filter-method="filterMethod"
          v-model="form.customer_ids"
          :props="{
            key: 'id',
            label: 'id'
          }"
          :data="customers"
          filterable
          filter-placeholder="Filter Customer"/> -->

      </el-col>
    </el-row>
  </vx-card>
</template>

<script>
// import CreateRoutineModal from './partials/CreateRoutineModal.vue'
import Resource from '@/api/resource';
export default {
  props: {
    reps: {
      type: Array,
      default: () => ([]),
    }, // make the <FullCalendar> tag available
  },
  data() {
    return {
      pickerOptions: {
        disabledDate(time) {
          return time.getTime() <= Date.now();
        },
      },
      options: {
        headings: {},
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        perPage: 10,
        filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['business_name', 'address'],
        filterable: ['business_name', 'address'],
      },
      form: {
        rep: '',
        customer_ids: [],
        schedule_date: '',
        note: 'Appointment Schedule',
        repeat_schedule: 'no',
      },
      load: false,
      load_customer: false,
      customers: [],
      selected_rep: '',
    };
  },
  methods: {
    // filterMethod(query, item) {
    //   return item.label.toLowerCase().indexOf(query.toLowerCase()) > -1;
    // },
    fetchCustomers(rep_id) {
      const app = this;
      app.form.rep = rep_id;
      app.load_customer = true;
      const customerResource = new Resource('customers/rep-customers');
      const param = { rep_id };
      customerResource.list(param)
        .then(response => {
          app.customers = response.customers;
          app.load_customer = false;
        });
    },
    submitSchedule() {
      const app = this;
      app.load = true;
      const submitScheduleResource = new Resource('schedules/store-rep-schedule');
      submitScheduleResource.store(app.form)
        .then(() => {
          app.$message('Schedule Saved Successfully');
          app.resetForm();
          app.load = false;
        }).catch(() => {
          app.load = false;
        });

      // console.log(events)
    },
    resetForm() {
      const app = this;
      app.customers = [];
      app.selected_rep = '';
      app.form = {
        rep: '',
        customer_ids: [],
        schedule_date: '',
        note: 'Appointment Schedule',
        repeat_schedule: 'no',
      };
    },
  },
};
</script>
