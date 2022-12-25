<template>
  <div v-loading="load" class="vx-row">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Action</th>
          <th>Rep</th>
          <th>Customer</th>
          <th>Purpose</th>
          <th>Date</th>
          <th>Coordinates</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(rep_entry, index) in rep_entries" :key="index">
          <td>
            <div class="demo-alignment">
              <vs-button v-if="rep_entries.length > 1" radius color="danger" icon-pack="feather" icon="icon-trash" @click="removeRepEntry(index)" />
              <vs-button v-if="index + 1 === rep_entries.length" radius color="primary" icon-pack="feather" icon="icon-plus" @click="addRepEntry(index)" />
            </div>
          </td>
          <td>
            <el-select v-model="rep_entry.rep_id" filterable style="width: 100%" @change="fetchCustomers($event, index)">
              <el-option
                v-for="(rep, rep_index) in reps"
                :key="rep_index"
                :label="rep.name"
                :value="rep.id"

              />
            </el-select>
          </td>
          <td>
            <el-button
              v-if="rep_entry.customer_id === ''"
              round
              class="filter-item"
              type="primary"
              @click="requestCustomer(index)"
            >Select Customer
            </el-button>
            <el-button
              v-else
              round
              class="filter-item"
              type="warning"
              @click="requestCustomer(index)"
            >Change Customer
            </el-button>
            <br>
            {{ rep_entry.business_name }}
            <!-- <el-select
              v-model="rep_entry.customer_id"
              placeholder="Select Customer"
              filterable
              style="width: 100%"
            >
              <el-option
                v-for="(cust, cust_index) in customersList"
                :key="cust_index"
                :value="cust.id"
                :label="cust.business_name + ' ' + cust.address"
              >
                <span style="float: left"><strong>{{ cust.business_name }} [{{ cust.code }}]</strong></span>
                <span style="float: right; color: #8492a6; font-size: 12px">{{ cust.address }}</span>
              </el-option>
            </el-select> -->
          </td>
          <!-- <td>0</td> -->
          <td>
            {{ rep_entry.purpose }}
            <!-- <el-input
              v-model="rep_entry.purpose"
              placeholder="Specify purpose(s)"
              type="textarea"
            /> -->
          </td>
          <td>
            <el-date-picker
              v-model="rep_entry.entry_date"
              :picker-options="pickerOptions"
              type="date"
              placeholder="Transaction Date"
              style="width: 100%;"
              format="yyyy-MM-dd"
              value-format="yyyy-MM-dd"
            />
          </td>
          <td>
            <strong>Rep's</strong>
            <el-input
              v-model="rep_entry.rep_coordinate"
              placeholder="6.5270061,3.3766094"
            />
            <br>
            <strong>Manager's</strong>
            <el-input
              v-model="rep_entry.manager_coordinate"
              placeholder="6.5270161,3.3756094"
            />
          </td>
          <!-- <td>
            <input
              type="file"
              class="form-control"
              multiple
              @change="onImageChange($event, index)"
            ><br>
            <span v-for="(file, file_index) in uploadedFiles[index].files" :key="file_index">
              <img :src="`/storage/${file}`" width="50">
            </span>
          </td> -->
        </tr>
        <tr v-if="fill_rep_fields_error">
          <td colspan="6">
            <label
              class="label label-danger"
            >Please fill all empty fields before adding another row</label>
          </td>
        </tr>
        <tr v-if="!isRepRowEmpty()" >
          <td colspan="6">
            <el-button round type="success" @click="submitVisitsReport()">Submit Report</el-button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<script>
import Resource from '@/api/resource';
import { createUniqueString } from '@/utils/index';

export default {
  props: {
    teamId: {
      type: Number,
      default: () => null,
    },
    reps: {
      type: Array,
      default: () => [],
    },
    selectedCustomer: {
      type: Object,
      default: () => null,
    },
  },
  data() {
    return {
      pickerOptions: {
        disabledDate(date) {
          var d = new Date(); // today
          d.setDate(d.getDate());
          return date >= d;
        },
      },
      rep_entries: [],
      activeName: '1',
      customer_name: '',
      selected_index: '',
      total: 0,
      dialogVisible: false,
      fill_fields_error: false,
      fill_rep_fields_error: false,
      showSaveButton: true,
      extra_customers: '',
      // eslint-disable-next-line no-array-constructor
      uploadedFiles: [],
      products: [],
      load: false,
      rowIndex: '',
    };
  },
  watch: {
    selectedCustomer(){
      // console.log('Value Changed');
      const app = this;
      const customer = app.selectedCustomer;
      const rowIndex = app.rowIndex;
      this.setCustomerDetails(customer, rowIndex);
    },
  },
  created() {
    this.addRepEntry();
  },
  methods: {
    requestCustomer(index) {
      const app = this;
      app.rowIndex = index;
      app.$emit('selectCustomer', 'others');
    },
    setCustomerDetails(customer, rowIndex){
      const app = this;
      app.rep_entries[rowIndex].customer_id = customer.id;
      app.rep_entries[rowIndex].business_name = customer.business_name;
    },
    isRepRowEmpty() {
      const checkEmptyLines = this.rep_entries.filter(
        (detail) =>
          detail.rep_id === '' ||
          detail.customer_id === '' ||
          detail.rep_coordinate === ''
      );
      if (checkEmptyLines.length > 0) {
        return true;
      }
      return false;
    },
    addRepEntry() {
      this.fill_rep_fields_error = false;

      if (this.isRepRowEmpty()) {
        this.fill_rep_fields_error = true;
        // this.hospital_visit_details[index].seleted_category = true;
        return;
      } else {
        // if (this.hospital_visit_details.length > 0)
        //     this.hospital_visit_details[index].grade = '';
        this.rep_entries.push({
          rep_id: '',
          customersList: [],
          business_name: '',
          customer_id: '',
          entry_date: new Date(),
          unique_visits_id: createUniqueString(),
          purpose: 'Others',
          rep_coordinate: '',
          manager_coordinate: '',
        });
      }
    },
    removeRepEntry(detailId) {
      this.fill_rep_fields_error = false;
      if (!this.repBlockRemoval) {
        this.rep_entries.splice(detailId, 1);
      }
    },
    fetchCustomers(rep_id, index) {
    //   const app = this;
    //   // if (!app.hideCustomersList) {
    //   const customerResource = new Resource('customers/rep-customers');
    //   const param = { rep_id, team_id: app.teamId };
    //   customerResource.list(param)
    //     .then(response => {
    //       // app.customers = response.customers;
    //       app.rep_entries[index].customersList = response.customers;
    //     });
    //   // }
    },
    submitVisitsReport() {
      const app = this;
      app.$confirm('Are you sure you want to submit these visit entries?', 'Warning', {
        confirmButtonText: 'Yes Submit',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        this.$message({
          type: 'info',
          message: 'Sending...',
        });
        app.load = true;
        if (app.rep_entries.length > 0) {
          const formattedEntries = [];
          app.rep_entries.forEach(entry => {
            formattedEntries.push({
              rep_id: entry.rep_id,
              customer_id: entry.customer_id,
              entry_date: entry.entry_date,
              unique_visits_id: entry.unique_visits_id,
              purpose: entry.purpose,
              rep_coordinate: entry.rep_coordinate,
              manager_coordinate: entry.manager_coordinate,
            });
          });
          const unsaved_visits = { unsaved_visits: formattedEntries };
          const storeResource = new Resource('visits/store');
          storeResource.store(unsaved_visits).then(() => {
            this.$message({
              type: 'success',
              message: 'Visit Entries Submitted',
            });
            app.rep_entries = [];
            app.addRepEntry();
            app.load = false;
          }).catch(() => {
            this.$message({
              type: 'danger',
              message: 'An error Occured',
            });
            app.load = false;
          });
        }
        // app.loadForm = false;
      }).catch(() => {
        app.load = false;
      });
    },
  },
};
</script>
