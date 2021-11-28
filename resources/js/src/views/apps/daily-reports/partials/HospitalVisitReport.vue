<template>
  <div class="vx-row">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Action</th>
          <th>Hospital</th>
          <!-- <th>Closing Debt</th> -->
        </tr>
      </thead>
      <tbody>
        <tr v-for="(customer, index) in visitedCustomersList" :key="index">
          <td>
            <el-tooltip :content="'Add Sales Report for ' + customer.business_name" class="item" effect="dark" placement="top-start">
              <el-button circle type="primary" icon="el-icon-goods" @click="setVisitDetails(index, customer)" />
            </el-tooltip>
          </td>
          <td>{{ customer.business_name }}</td>
        </tr>
      </tbody>
    </table>
    <el-dialog
      :visible.sync="dialogVisible"
      :title="'Visit Details for ' + customer_name"
      width="90%">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th/>
            <th>Personnel Contacted</th>
            <th>Marketed Products</th>
            <th>Follow-up Schedule</th>
            <th>Feedback/Comment</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(detail, index) in hospital_visit_details" :key="index">
            <td>
              <div class="demo-alignment">
                <vs-button v-if="hospital_visit_details.length > 1" radius color="danger" icon-pack="feather" icon="icon-trash" @click="removeLine(index)" />
                <vs-button v-if="index + 1 === hospital_visit_details.length" radius color="success" icon-pack="feather" icon="icon-plus" @click="addLine(index)" />
              </div>
            </td>
            <td>
              <el-select
                v-model="detail.hospital_contacts"
                placeholder="Select Contact"
                filterable
                style="width: 100%"
                collapse-tags
              >
                <el-option
                  v-for="(contact, item_index) in contacts"
                  :key="item_index"
                  :value="contact.name + ' (' + contact.phone1 + ')'"
                  :label="contact.name + ' (' + contact.phone1 + ')'"
                />
              </el-select>
            </td>
            <td>
              <el-select
                v-model="detail.marketed_products_to_hospitals"
                placeholder="Select Product"
                filterable
                collapse-tags
                style="width: 100%"
                multiple
              >
                <el-option
                  v-for="(product, item_index) in products"
                  :key="item_index"
                  :value="product.item.name"
                  :label="product.item.name"
                />
              </el-select>
            </td>
            <td>
              <el-date-picker
                v-model="detail.hospital_follow_up_schedule"
                :picker-options="pickerOptions"
                type="datetime"
                default-time="09:00:00"
                placeholder="Schedule Date"
                style="width: 100%;"
              />
            </td>
            <td>
              <el-input
                v-model="detail.hospital_feedback"
                type="textarea"
                autosize
                maxlength="160"
                show-word-limit
                placeholder="Give feedback"/>
            </td>
          </tr>
          <tr v-if="fill_fields_error">
            <td colspan="4">
              <label
                class="label label-danger"
              >Please fill all empty fields before adding another row</label>
            </td>
          </tr>
        </tbody>
      </table>
      <span slot="footer" class="dialog-footer">
        <el-button @click="dialogVisible = false">Cancel</el-button>
        <el-button type="primary" @click="addVisitDetails()">Done</el-button>
      </span>
    </el-dialog>
  </div>
</template>
<script>
export default {
  props: {
    visitedCustomersList: {
      type: Array,
      default: () => [],
    },
    myCustomers: {
      type: Array,
      default: () => [],
    },
    products: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      pickerOptions: {
        disabledDate(date) {
          var d = new Date(); // today
          d.setDate(d.getDate());
          return date < d;
        },
      },
      dialogVisible: false,
      fill_fields_error: false,
      hospital_visit_details: [],
      contacts: [],
      selected_index: '',
      customer_name: '',
    };
  },
  methods: {
    setVisitDetails(index, customer) {
      this.selected_index = index;
      this.hospital_visit_details = [];
      if (!customer.hospital_visit_details) {
        this.addLine();
      } else {
        this.hospital_visit_details = customer.hospital_visit_details;
      }
      this.contacts = customer.customer_contacts;
      this.customer_name = customer.business_name;
      this.dialogVisible = true;
    },
    addLine() {
      this.fill_fields_error = false;

      const checkEmptyLines = this.hospital_visit_details.filter(
        (detail) =>
          detail.hospital_contacts === '' ||
          detail.hospital_feedback === ''
      );

      if (checkEmptyLines.length >= 1 && this.hospital_visit_details.length > 0) {
        this.fill_fields_error = true;
        // this.hospital_visit_details[index].seleted_category = true;
        return;
      } else {
        // if (this.hospital_visit_details.length > 0)
        //     this.hospital_visit_details[index].grade = '';
        this.hospital_visit_details.push({
          item_index: null,
          marketed_products_to_hospitals: [],
          hospital_follow_up_schedule: '',
          hospital_contacts: '',
          hospital_feedback: '',
        });
      }
    },
    removeLine(detailId) {
      this.fill_fields_error = false;
      if (!this.blockRemoval) {
        this.hospital_visit_details.splice(detailId, 1);
        this.calculateTotal(null);
      }
    },
    addVisitDetails() {
      const app = this;
      const hospital_visit_details = app.hospital_visit_details;
      app.visitedCustomersList[app.selected_index].hospital_visit_details = hospital_visit_details;
      // console.log(app.visitedCustomersList[app.selected_index]);
      this.dialogVisible = false;
    },
  },
};
</script>
