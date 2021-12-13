<template>
  <!-- <ion-content> -->
  <div class="overlay-panel">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th/>
          <th>Contact Name</th>
          <th>Primay Phone No.</th>
          <th>Alternative No.</th>
          <th>Contact Role</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(contact, index) in customer_contacts" :key="index">
          <td>
            <div class="demo-alignment">
              <vs-button v-if="customer_contacts.length > 1" radius color="danger" icon-pack="feather" icon="icon-trash" @click="removeLine(index)" />
              <vs-button v-if="index + 1 === customer_contacts.length" radius color="success" icon-pack="feather" icon="icon-plus" @click="addLine(index)" />
            </div>
          </td>
          <td>
            <el-input
              v-model="contact.name"
              type="text"
              outline
              placeholder="Contact Name"
            />
          </td>
          <td>
            <el-input
              v-model="contact.phone1"
              type="number"
              outline
              placeholder="Primary Phone Number"
              max="11"
          /></td>
          <td>
            <el-input
              v-model="contact.phone2"
              type="number"
              outline
              placeholder="Alternative Phone Number"
              max="11"
            />
          </td>
          <td>
            <el-input
              v-model="contact.role"
              type="text"
              outline
            />
          </td>
        </tr>
        <tr v-if="fill_fields_error">
          <td colspan="5">
            <label
              class="label label-danger"
            >Please fill all empty fields before adding another row</label>
          </td>
        </tr>
      </tbody>
    </table>
    <span slot="footer" class="dialog-footer">
      <el-button type="primary" @click="save()">Done</el-button>
    </span>
  </div>
</template>
<script>
import Resource from '@/api/resource';
export default {
  name: 'AddNewContact',
  props: {
    customerContacts: { type: Array, default: () => ([]) },
    customer: { type: Object, default: () => ({}) },
  },

  data() {
    return {
      customer_contacts: [],
      fill_fields_error: false,
      blockRemoval: false,
    };
  },

  watch: {
    customer_contacts() {
      this.blockRemoval = this.customer_contacts.length < 1;
    },
  },
  created() {
    this.customer_contacts = this.customerContacts;
    if (this.customer_contacts.length < 1) {
      this.addLine();
    }
  },
  methods: {
    closeForm(){
      this.removeEmptyEntries();
      this.$emit('close', 'add_customer');
    },
    setOpen(value){
      const app = this;
      app.fill_fields_error = value;
    },
    addLine() {
      this.setOpen(false);

      if (this.hasEmptyField()) {
        this.setOpen(true);
        // this.customerContacts[index].seleted_category = true;
        return;
      } else {
        this.customer_contacts.push({
          name: '',
          phone1: '',
          phone2: '',
          role: '',
        });
      }

    // console.log(this.customer_contacts)
      // this.scrollToBottom();
    },
    removeLine(detailId) {
      this.setOpen(false);
      if (!this.blockRemoval) {
        this.customer_contacts.splice(detailId, 1);
      }
    },
    hasEmptyField(){
      const checkEmptyLines = this.customer_contacts.filter(
        (detail) =>
          detail.name === '' ||
          detail.phone1 === '' ||
          detail.role === ''
      );

      if (checkEmptyLines.length >= 1 && this.customer_contacts.length > 0) {
        return true;
      }
      return false;
    },
    removeEmptyEntries(){
      this.customer_contacts.forEach(detail => {
        const index = this.customer_contacts.indexOf(detail);
        if (detail.name === '' || detail.phone1 === '' || detail.role === '') {
          this.customer_contacts.splice(index, 1);
        }
      });
    },
    save() {
      const app = this;
      if (!app.hasEmptyField) {
        const form = { customer_id: app.customer.id, customer_contacts: app.customer_contacts };
        // send to database
        const storeResource = new Resource('customers/add-customer-contact');
        // app.openLoader = true;
        storeResource.store(form)
          .then((response) => {
            app.$emit('save', response);
          })
          .catch(() => {
            // If there is an error, we attempt to save the customer temporarily and retry sending again
            //   const unsaved_customers = this.unsaved_customers;
            //   unsaved_customers.unshift(form);
            //   this.setUnsavedCustomers(JSON.stringify(unsaved_customers));
            //   app.openLoader = false;
            //   console.log(error);
          });
      } else {
        app.$alert('Please fill all empty fields');
      }
    },
  },
};
</script>
<style scoped>
ion-fab-button[data-desc] {
  position: relative;
}

ion-fab-button[data-desc]::after {
  position: absolute;
  content: attr(data-desc);
  z-index: 1;
  left: 50px;
  bottom: 8px;
  background-color: rgba(0,0,0,0.7);
  padding: 6px;
  border-radius: 5px;
  color: #ffffff;
  font-size: 10px;
  box-shadow: 0 3px 5px -1px rgba(0,0,0,0.2), 0 6px 10px 0 rgba(0,0,0,0.14), 0 1px 18px 0 rgba(0,0,0,0.12);
}

</style>
