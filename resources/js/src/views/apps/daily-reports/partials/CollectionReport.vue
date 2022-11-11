<template>
  <div class="vx-row">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Customer Name</th>
          <!-- <th>Opening Debt</th> -->
          <th>Amount Collected (NGN)</th>
          <th>Date</th>
          <th>Slip No.</th>
          <th>Rep Coord.</th>
          <th>Attach File</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(customer, index) in visitedCustomersList" :key="index">
          <td>
            {{ customer.business_name }}
            <el-button v-if="customer.can_delete === 'yes'" circle type="danger" icon="el-icon-delete" @click="removeExtraCustomer(customer.id)" />
          </td>
          <!-- <td>0</td> -->
          <td>
            <el-input
              v-model="customer.amount_collected"
              type="number"
              outline
              placeholder="Amount Collected"
              min="1"
            /><br>
            <strong>Payment Method</strong>
            <el-select
              v-model="customer.payment_mode"
              style="width: 100%"
            >
              <el-option value="Cash" label="Cash" />
              <el-option value="Cheque" label="Cheque" />
              <el-option value="Bank Deposit/Transfer" label="Bank Deposit/Transfer/POS" />
            </el-select>
          </td>
          <td>
            <el-date-picker
              v-model="customer.entry_date"
              :picker-options="pickerOptions"
              type="date"
              placeholder="Transaction Date"
              style="width: 100%;"
              format="yyyy-MM-dd"
              value-format="yyyy-MM-dd"
            />
          </td>
          <td>
            <el-input
              v-model="customer.slip_no"
              type="text"
              outline
              placeholder="Receipt|Teller|Cheque No."
              style="width: 100%"
            />
          </td>
          <td>
            <strong>Rep's</strong>
            <el-input
              v-model="customer.rep_coordinate"
              placeholder="6.5270061,3.3766094"
            />
            <br>
            <strong>Manager's</strong>
            <el-input
              v-model="customer.manager_coordinate"
              placeholder="6.5270161,3.3756094"
            />
          </td>
          <td>
            <input
              type="file"
              class="form-control"
              multiple
              @change="onImageChange($event, index)"
            ><br>
            <span v-for="(file, file_index) in uploadedFiles[index].files" :key="file_index">
              <img :src="`/storage/${file}`" width="50">
            </span>
          </td>
        </tr>
        <tr>
          <td colspan="8">
            <el-select
              v-model="extra_customers"
              placeholder="Select Customer"
              filterable
              style="width: 100%"
              @input="addExtraCustomers($event)"
            >
              <el-option
                v-for="(extra_customer, item_index) in myCustomers"
                :key="item_index"
                :value="item_index"
                :label="extra_customer.business_name + ' ' + extra_customer.address"
              >
                <span style="float: left"><strong>{{ extra_customer.business_name }}</strong></span>
                <span style="float: right; color: #8492a6; font-size: 12px">{{ extra_customer.address }}</span>
              </el-option>
            </el-select>
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
    visitedCustomersList: {
      type: Array,
      default: () => [],
    },
    myCustomers: {
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
          return date >= d;
        },
      },
      extra_customers: '',
      uploadedFiles: [],
    };
  },
  methods: {
    addExtraCustomers(value) {
      const app = this;
      const customer_id = app.myCustomers[value].id;
      if (!app.visitedCustomersList.filter(e => e.id === customer_id).length > 0) {
        app.myCustomers[value].customer_id = customer_id;
        // app.myCustomers[value].payment_mode = 'Cash';
        app.myCustomers[value].collections_files = [];
        app.myCustomers[value].can_delete = 'yes';
        app.myCustomers[value].unique_collection_id = createUniqueString();
        app.visitedCustomersList.push(app.myCustomers[value]);
        app.uploadedFiles.push({ files: [] });
      }
      app.extra_customers = '';
    },
    removeExtraCustomer(customer_id) {
      const app = this;
      for (let count = 0; count < app.visitedCustomersList.length; count++) {
        if (app.visitedCustomersList[count].id === customer_id) {
          app.visitedCustomersList.splice(count, 1);
        }
      }
    },
    // onImageChange(e, index) {
    //   const app = this;
    //   const filesToBeUploaded = e.target.files[0];
    //   app.submitUpload(filesToBeUploaded, index);
    //   // app.visitedCustomersList[index].files = filesToBeUploaded;
    // },
    onImageChange(e, index) {
      const app = this;
      const files = e.target.files;
      let filesToBeUploaded = '';
      for (let i = 0; i < files.length; i++) {
        filesToBeUploaded = e.target.files[i];
        // console.log(file);
        // const filesToBeUploaded = file[0];
        app.submitUpload(filesToBeUploaded, index);
      }
      // app.visitedCustomersList[index].files = filesToBeUploaded;
    },
    submitUpload(filesToBeUploaded, index) {
      const app = this;
      app.loading = true;
      const formData = new FormData();
      formData.append('files', filesToBeUploaded);
      formData.append('type', 'collections');
      const updatePhotoResource = new Resource('attach/files');
      updatePhotoResource.store(formData)
        .then(response => {
          app.visitedCustomersList[index].collections_files.push(response);
          app.uploadedFiles[index].files.push(response);
        })
        .catch(e => {
          console.log(e);
        });
    },
  },
};
</script>
