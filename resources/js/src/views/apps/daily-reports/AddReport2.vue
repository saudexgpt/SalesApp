<template>
  <div class="app-container">
    <!-- <span v-if="params">
      <router-link
        :to="{name:'Invoices'}"
        class="btn btn-default"
      >View Invoices</router-link>
    </span> -->

    <el-card>
      <div class="box">
        <div class="box-header">
          <h4 class="box-title">Create New Invoice</h4>
          <span class="pull-right">
            <a
              v-if="checkPermission(['create invoice']) && upload_type ==='normal'"
              class="btn btn-success"
              @click="upload_type ='bulk'"
            >Bulk Upload</a>
            <a
              v-if="checkPermission(['create invoice']) && upload_type ==='bulk'"
              class="btn btn-primary"
              @click="upload_type ='normal'"
            >Normal Upload</a>
            <router-link
              v-if="checkPermission(['view invoice'])"
              :to="{name:'Invoices'}"
              class="btn btn-danger"
            >Cancel</router-link>

          </span>
        </div>
        <div class="box-body">
          <div>
            <el-row :gutter="2" class="padded">
              <el-col>
                <div style="overflow: auto">
                  <el-date-picker
                    v-model="form.date"
                    :picker-options="pickerOptions"
                    type="date"
                    placeholder="Report Date"
                    style="width: 100%;"
                    format="yyyy/MM/dd"
                    value-format="yyyy-MM-dd"
                  />
                  <label for>Customer Report</label>
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th />
                        <th>Customer Name</th>
                        <th>Closing Debt</th>
                        <th>Visited</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(cust_report, index) in customers_report" :key="index">
                        <td>
                          <div class="demo-alignment">
                            <vs-button radius color="danger" icon-pack="feather" icon="icon-trash" @click="removeLine(index)" />
                            <vs-button v-if="index + 1 === customers_report.length" radius color="success" icon-pack="feather" icon="icon-plus" @click="addLine(index)" />
                          </div>
                        </td>
                        <td>
                          <el-select
                            v-model="cust_report.customer_id"
                            placeholder="Select Customer"
                            filterable
                            style="width: 100%"
                          >
                            <el-option
                              v-for="(customer, customer_index) in customers"
                              :key="customer_index"
                              :value="customer.id"
                              :label="customer.business_name"
                            />
                          </el-select>
                        </td>
                        <td>
                          <el-input
                            v-model="cust_report.opening_debt"
                            type="number"
                            outline
                            placeholder="Closing Debt"
                            min="1"
                          />
                        </td>
                        <td>
                          <el-switch
                            v-model="cust_report.visited"
                            active-color="#13ce66"
                            inactive-color="#ff4949"
                            active-text="Yes"
                            inactive-text="No"/>
                        </td>
                      </tr>
                      <tr v-if="fill_fields_error">
                        <td colspan="4">
                          <label
                            class="label label-danger"
                          >Please fill all empty fields before adding another row</label>
                        </td>
                      </tr>
                      <tr>
                        <td>Extra information</td>
                        <td>
                          <strong>Did you work with your manager today?</strong>
                          <el-switch
                            v-model="form.work_with_manager_check"
                            active-color="#13ce66"
                            inactive-color="#ff4949"
                            active-text="Yes"
                            inactive-text="No"/>
                          <br><br><br>
                          <div v-if="form.work_with_manager_check">

                            <strong>How long did you work with your manager today?</strong>
                            <el-input
                              v-model="form.time_duration_with_manager"
                              type="text"
                              outline
                              placeholder="Type time spent with manager today"
                            />
                          </div>
                        </td>
                        <td colspan="2">
                          <div v-if="form.work_with_manager_check">
                            <strong>How was your relationship with your manager today?</strong>
                            <textarea
                              v-model="form.relationship_with_manager"
                              class="form-control"
                              rows="3"
                              placeholder="Briefly describe your manager-staff relationship today"
                            />
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </el-col>
            </el-row>
            <el-row :gutter="2" class="padded">
              <el-col :xs="24" :sm="6" :md="6">
                <el-button type="success" @click="submitReport()">
                  <i class="el-icon-plus" />
                  Submit Invoice
                </el-button>
              </el-col>
            </el-row>
          </div>

        </div>
      </div>
    </el-card>

  </div>
</template>

<script>
import moment from 'moment';
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';
import Resource from '@/api/resource';
export default {
  name: 'NewReport',

  data() {
    return {
      pickerOptions: {
        disabledDate(date) {
          var d = new Date(); // today
          d.setDate(d.getDate()); // one year from now
          return date > d;
        },
      },
      upload_type: 'normal',
      customers: [],
      customer_types: [],
      items_in_stock_dialog: false,
      dialogFormVisible: false,
      userCreating: false,
      fill_fields_error: false,
      show_product_list: false,
      batches_of_items_in_stock: [],
      form: {
        work_with_manager_check: false,
        time_duration_with_manager: null,
        relationship_with_manager: null,
        date: '',
        customers_report: [
          {
            customer_id: '',
            visited: false,
            opening_debt: 0,
          },
        ],
      },
      empty_form: {
        work_with_manager_check: false,
        time_duration_with_manager: null,
        relationship_with_manager: null,
        date: '',
        customers_report: [
          {
            customer_id: '',
            visited: false,
            opening_debt: 0,
          },
        ],
      },
      customers_report: [],
      rules: {
        customer_type: [
          {
            required: true,
            message: 'Customer Type is required',
            trigger: 'change',
          },
        ],
        name: [
          { required: true, message: 'Name is required', trigger: 'blur' },
        ],
        // email: [
        //   { required: true, message: 'Email is required', trigger: 'blur' },
        //   { type: 'email', message: 'Please input correct email address', trigger: ['blur', 'change'] },
        // ],
        // phone: [{ required: true, message: 'Phone is required', trigger: 'blur' }],
      },
    };
  },
  computed: {
    params() {
      return this.$store.getters.params;
    },
  },
  watch: {
    customers_report() {
      this.blockRemoval = this.customers_report.length <= 1;
    },
  },
  mounted() {
    this.fetchCustomers();
    this.addLine();
  },
  methods: {
    moment,
    checkPermission,
    checkRole,
    addLine(index) {
      this.fill_fields_error = false;

      const checkEmptyLines = this.customers_report.filter(
        (detail) =>
          detail.customer_id === '' ||
          detail.opening_debt === '' ||
          detail.visited === null
      );

      if (checkEmptyLines.length >= 1 && this.customers_report.length > 0) {
        this.fill_fields_error = true;
        // this.customers_report[index].seleted_category = true;
        return;
      } else {
        // if (this.customers_report.length > 0)
        //     this.customers_report[index].grade = '';
        this.customers_report.push({
          customer_id: '',
          visited: false,
          opening_debt: 0,
        });
      }
    },
    removeLine(detailId) {
      this.fill_fields_error = false;
      if (!this.blockRemoval) {
        this.customers_report.splice(detailId, 1);
      }
    },
    fetchCustomers() {
      const app = this;
      const getCustomers = new Resource('customers/all');
      getCustomers.list().then((response) => {
        app.customers = response.customers;
      });
    },
    submitReport() {
      const app = this;
      var form = app.form;
      const createReport = new Resource('daily-report/store');
      const checkEmptyFielads = form.date === '';
      if (!checkEmptyFielads) {
        app.load = true;
        form.customers_report = app.customers_report;
        createReport
          .store(form)
          .then((response) => {
            app.$message({
              message: 'Report Saved Successfully!!!',
              type: 'success',
            });
            app.form = app.empty_form;
            app.customers_report = app.form.customers_report;
            app.$router.push({ name: 'ViewAll' });
            app.load = false;
          })
          .catch((error) => {
            app.load = false;
            console.log(error.message);
          });
      } else {
        alert('Please fill the form fields completely');
      }
    },
  },
};
</script>

