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

        <el-button :disabled="form.customer_ids.length < 1" type="primary" round @click="calculateDistance(); show_submit_button = true">
          Preview
        </el-button>
        <el-button v-if="show_submit_button" :disabled="form.customer_ids.length < 1" type="success" round @click="submitSchedule()">
          Submit Schedule
        </el-button>
        <br><br>
        <div v-if="rearranged_schedule.length > 0">
          <strong>Ordered Movement based on proximity WRT the starting point</strong><br>
          <el-tag
            v-for="(tag, index ) in rearranged_schedule"
            :key="index"
            type="success"
            effect="plain"
          >
            {{ index + 1 }} {{ (tag) ? tag.business_name : '' }}
          </el-tag>
        </div>
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
        <div v-if="!show_map">
          <hr>
          <el-select
            v-if="no_of_customers_array.length > 0"
            v-model="no_of_customers_to_assign"
            filterable
            style="width: 100%"
            placeholder="Select the number of customers to assign"
            multiple
            @change="setCustomers($event)">
            <el-option
              v-for="(no_of_cust, index) in no_of_customers_array"
              :key="index"
              :label="no_of_cust.label"
              :value="no_of_cust.value"

            />
          </el-select>
          <hr>
          <v-client-table
            ref="myTable"
            v-model="customers"
            :columns="[
              'select',
              'business_name',
              'address',
            ]"
            :options="options"
            @filter="setNumberOfCustomersArray"
          >
            <template slot="business_name" slot-scope="scope">
              <strong>{{ scope.row.business_name }}</strong><br>
              <small><strong>Coordinate: </strong>{{ scope.row.longitude }}, {{ scope.row.latitude }}</small>
            </template>
            <template slot="select" slot-scope="scope">
              <el-checkbox v-model="form.customer_ids" :label="scope.row.id" border/>
            </template>
          </v-client-table>
        </div>
        <div v-if="show_map">
          <el-button type="danger" round @click="show_map = false">
            Go Back
          </el-button>
          <gmap-map
            :center="center"
            :zoom="zoom"
            style="width:100%;  height: 650px"
          >
            <gmap-marker
              v-for="(m, index) in markers"
              :key="index"
              :position="m.position"
              :icon="m.icon"
              @mouseout="showByIndex = null"
              @click="center=m.position; showDetails(m.detail)"
              @mouseover="showByIndex = index;"
            >
              <gmap-info-window
                :opened="showByIndex === index"
              >
                <strong>Business Name: </strong> {{ m.detail.business_name }}<br>
                <strong>Customer Registered Address: </strong> {{ m.detail.address }}<br>
              </gmap-info-window>
            </gmap-marker>

            <gmap-polyline :path.sync="paths" :options="{ strokeColor:'#000000'}" />
          </gmap-map>
        </div>

      </el-col>
    </el-row>
  </vx-card>
</template>

<script>
// import CreateRoutineModal from './partials/CreateRoutineModal.vue'
import GmapCluster from 'vue2-google-maps/dist/components/cluster';
import GmapPolyline from 'vue2-google-maps/dist/components/polyline';
import Resource from '@/api/resource';
export default {
  components: {
    GmapCluster,
    GmapPolyline,
  },
  props: {
    reps: {
      type: Array,
      default: () => ([]),
    }, // make the <FullCalendar> tag available
  },
  data() {
    return {
      show_map: false,
      show_submit_button: false,
      markers: [],
      paths: [],
      showByIndex: null,
      center: { lat: 6.546935900, lng: 3.365565100 }, // default to greenlife office
      zoom: 12,
      pickerOptions: {
        disabledDate(time) {
          return time.getTime() <= Date.now();
        },
      },
      options: {
        headings: {},
        rowAttributesCallback(row) {
          if (row.registered_by === 1) {
            return { style: 'background: #00b95df8; color: #000000' };
          }
          // return { style: 'background: #36c15ecf; color: #000000' };
        },
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
      selected_customers: [],
      rearranged_schedule: [],
      no_of_customers_to_assign: [],
      no_of_customers_array: [],
    };
  },
  created() {
    this.setNumberOfCustomersArray();
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
    setNumberOfCustomersArray(){
      const app = this;
      const filtered_customers = app.customers; // this.$refs.myTable.allFilteredData;
      const no_of_customers_array = [];
      const filtered_customers_count = filtered_customers.length;
      const whole_num = Math.floor(filtered_customers_count / 10);
      const modulus = filtered_customers_count % 10;
      for (let index = 1; index <= whole_num; index++) {
        const label = (index === 1) ? 'First' : 'Next';
        const val = 10;
        no_of_customers_array.push({
          value: (val * index),
          label: label + ' ' + val,
        });
      }
      no_of_customers_array.push({
        value: modulus,
        label: ' Last ' + modulus,
      });
      this.no_of_customers_array = no_of_customers_array;
    },
    setCustomers() {
      const filtered_customers = this.$refs.myTable.allFilteredData;
      const cust_ids = [];
      this.no_of_customers_to_assign.forEach(value => {
        let start = 0;
        let end = filtered_customers.length;
        if (value < 10) {
          start = filtered_customers.length - value;
          end = filtered_customers.length;
        } else {
          start = value - 10;
          end = value;
        }
        // console.log(this.$refs.myTable);
        for (let index = start; index < end; index++) {
          const customer = filtered_customers[index];
          if (customer) {
            cust_ids.push(customer.id);
          }
        }
      });

      //   filtered_customers.forEach(customer => {
      //     cust_ids.push(customer.id);
      //   });
      this.form.customer_ids = cust_ids;
    },
    calculateDistance(){
      const app = this;
      app.rearranged_schedule = [];
      const customers = app.form.customer_ids;
      const selected_customers = [];
      let latFrom = 0;
      let longFrom = 0;
      customers.forEach(id => {
        const customer = this.customers.filter(
          (cust) =>
            cust.id === id
        );
        if (latFrom === 0) {
          latFrom = customer[0].latitude;
          longFrom = customer[0].longitude;
        }
        selected_customers.push(customer[0]);
      });
      app.selected_customers = selected_customers;
      // eslint-disable-next-line no-array-constructor
      const rearranged_schedule = new Array();
      const unsorted_customers = [];
      // eslint-disable-next-line no-array-constructor
      const distance_array = new Array();
      selected_customers.forEach(each_customer => {
        const distance = app.getDistanceFromLatLonInKm(
          each_customer.latitude,
          each_customer.longitude,
          latFrom,
          longFrom,
        );
        let index = parseInt(distance);
        let distance_index = distance_array.indexOf(index);
        for (let a = 0; a < 5; a++) {
          if (distance_array[distance_index] !== undefined) {
            distance_index++;
            index++;
          } else {
            break;
          }
        }

        distance_array.push(index);
        const obj = {
          distance: parseInt(index),
          customer: each_customer,
        };
        unsorted_customers.push(obj);
        // // rearranged_schedule.splice(index, 0, obj);
        // // latFrom = each_customer.latitude;
        // // longFrom = each_customer.longitude;
        // rearranged_schedule[index] = obj;
        // rearranged_schedule.push({
        //   distance: index,
        //   customer: each_customer.business_name,
        // });
      });
      // we want to sort the distances in ascending order
      distance_array.sort(function(a, b){
        return a - b;
      });
      console.log(distance_array);
      // We now want to arrange the selected customers based on the indexes of their sorted distances
      unsorted_customers.forEach(customer => {
        let index = distance_array.indexOf(customer.distance);
        if (rearranged_schedule[index]) {
          index++;
        }
        rearranged_schedule[index] = customer.customer;
      });
      const customer_ids = [];
      rearranged_schedule.forEach(customer => {
        customer_ids.push(customer.id);
      });
      app.rearranged_schedule = rearranged_schedule;
      app.form.customer_ids = customer_ids;
      app.addMarker();
      // console.log(selected_customers);
    },
    addMarker() {
      const app = this;
      var markers = [];
      var paths = [];
      const icon = '/images/map-marker.png';
      app.rearranged_schedule.forEach(customer => {
        const position = {
          lat: customer.latitude,
          lng: customer.longitude,
        };

        paths.push(position);
        markers.push({ position: position, icon: icon, detail: customer });
      });
      this.paths = paths;
      this.markers = markers;
      this.show_map = true;
    },
    getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
      const app = this;
      var R = 6371000; // Radius of the earth in km
      var dLat = app.deg2rad(lat2 - lat1); // deg2rad below
      var dLon = app.deg2rad(lon2 - lon1);
      var a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(app.deg2rad(lat1)) * Math.cos(app.deg2rad(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2)
            ;
      var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      var d = R * c; // Distance in km
      return d;
    },

    deg2rad(deg) {
      return deg * (Math.PI / 180);
    },
  },
};
</script>
