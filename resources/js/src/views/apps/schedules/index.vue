<template>
  <div>
    <vx-card v-if="page === 'list'">
      <div class="vx-row">
        <div class="vx-col lg:w-3/4 w-full">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
            <span class="font-medium text-lg">Rep Schedule</span>
          </div>
          <vs-divider />
        </div>
        <div class="vx-col lg:w-1/4 w-full">
          <div class="flex items-end px-3">
            <span class="pull-right">
              <el-button
                round
                style="margin:0 0 20px 20px;"
                type="success"
                icon="el-icon-plus"
                size="small"
                @click="page = 'new_schedule'"
              >Add Schedule</el-button>
            </span>
          </div>
        </div>
      </div>
      <el-row :gutter="10">
        <el-col :lg="8" :md="8" :sm="8" :xs="24">
          <label for="">Select Rep</label>
          <el-select v-model="form.rep_id" filterable style="width: 100%" @input="setScheduleDetails()">
            <el-option
              v-if="reps.length > 0"
              label="All"
              value="all" />
            <el-option
              v-for="(rep, index) in reps"
              :key="index"
              :label="rep.name"
              :value="rep.id"

            />
          </el-select>
        </el-col>
      </el-row>
      <full-calendar
        v-loading="load"
        ref="refCalendar"
        :options="calendarOptions"
        class="full-calendar"
      />
    </vx-card>
    <div v-if="page==='new_schedule'">
      <div class="flex items-end px-3">
        <span class="pull-right">
          <el-button
            round
            type="danger"
            icon="el-icon-back"
            size="small"
            @click="page = 'list'"
          >Go Back</el-button>
        </span>
        <br>
      </div>
      <add-schedule :reps="reps" />
    </div>
  </div>
</template>

<script>
import moment from 'moment';
import FullCalendar from '@fullcalendar/vue';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import AddSchedule from './AddSchedule';
import Resource from '@/api/resource';
export default {
  components: {
    AddSchedule,
    FullCalendar, // make the <FullCalendar> tag available
  },
  data() {
    return {
      page: 'list',
      calendarOptions: {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        headerToolbar: {
          left: 'prev,next',
          // center: 'title',
          right: 'listWeek,timeGridDay,timeGridWeek',
          // left: '',
          center: '',
        //   right: '',
        },
        initialView: 'timeGridDay',
        // dateClick: this.handleDateClick,
        weekends: false,
        // editable: true,
        // eventResizableFromStart: true,

        slotMinTime: '08:00:00', // "07:45:00",
        slotMaxTime: '18:00:00',
        slotDuration: '00:05:00',
        events: [],
      },
      reps: [],
      form: {
        rep_id: '',
      },
      load: false,
    };
  },
  created() {
    this.fetchSalesReps();
    // this.setScheduleDetails();
  },
  methods: {
    moment,
    fetchSalesReps() {
      const app = this;
      // this.load_table = true;
      const salesRepResource = new Resource('users/fetch-sales-reps');
      salesRepResource
        .list()
        .then((response) => {
          app.reps = response.sales_reps;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    fetchCustomers(rep_id) {
      const app = this;
      app.load_customer = true;
      const customerResource = new Resource('customers/rep-customers');
      const param = { rep_id };
      customerResource.list(param)
        .then(response => {
          app.customers = response.customers;
          app.load_customer = false;
        });
      app.fetchDebts();
    },
    setScheduleDetails() {
      const app = this;
      app.load = true;
      const fetchScheduleResource = new Resource('schedules/fetch-reps');
      fetchScheduleResource.list(app.form)
        .then(response => {
          // const classTeacher = response.class_teacher
          const { schedules } = response;
          // app.form.class_teacher_id = classTeacher.id
          // app.form.subject_teacher_id = classTeacher.subject_teachers[0].id
          // app.subject_teachers = classTeacher.subject_teachers
          app.setEvents(schedules);
          app.load = false;
        });

      // console.log(events)
    },
    addHours(numOfHours, date = new Date()) {
      date.setTime(date.getTime() + numOfHours * 60 * 30 * 1000);

      return this.moment(date).format('HH:mm:ss');
    },
    randomColor() {
      const colorCodes = ['#fadcb6', '#c9fab6', '#c1fab6', '#b6faef', '#b6d6fa', '#bab6fa', '#e4b6fa', '#fab6b9'];
      const randomColor = colorCodes[Math.floor(Math.random() * colorCodes.length)];
      return randomColor;
    },
    setEvents(routines) {
      const app = this;
      const events = [];
      routines.forEach(routine => {
        const rep = (routine.rep) ? `${routine.rep.name}` : '';
        const customer = (routine.customer) ? `${routine.customer.business_name}` : '';
        const scheduled_by = (routine.scheduled_by) ? `${routine.scheduled_by.name}` : '';
        const eachEvent = {
          id: routine.id,
          title: `${customer} (${routine.note}) for ${rep} by ${scheduled_by}`,
          start: routine.schedule_date,
          end: this.schedule_date,
          startTime: routine.schedule_time,
          endTime: this.addHours(1, new Date(routine.schedule_date + 'T' + routine.schedule_time)),
          backgroundColor: app.randomColor(),
          textColor: '#000000',
          borderColor: 'white',
          daysOfWeek: [routine.day_num],
          startRecur: routine.schedule_date,
          endRecur: this.addHours(24, new Date(routine.schedule_date + 'T' + routine.schedule_time)),
          allDay: false,
        };
        events.push(eachEvent);
      });
      app.calendarOptions.events = events;
    },
  },
};
</script>
