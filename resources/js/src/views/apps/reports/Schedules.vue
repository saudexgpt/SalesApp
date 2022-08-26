<template>
  <vx-card>
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
</template>

<script>
import moment from 'moment';
import FullCalendar from '@fullcalendar/vue';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
// import CreateRoutineModal from './partials/CreateRoutineModal.vue'
import Resource from '@/api/resource';
export default {
  components: {
    FullCalendar, // make the <FullCalendar> tag available
  },
  data() {
    return {
      calendarOptions: {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        headerToolbar: {
          // left: 'prev,next',
          // center: 'title',
          right: 'timeGridWeek,timeGridDay,listWeek,dayGridMonth',
          left: '',
          center: '',
        //   right: '',
        },
        initialView: 'dayGridMonth',
        // dateClick: this.handleDateClick,
        weekends: false,
        // editable: true,
        // eventResizableFromStart: true,

        slotMinTime: '08:00:00', // "07:45:00",
        slotMaxTime: '18:00:00',
        slotDuration: '00:10:00',
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
      date.setTime(date.getTime() + numOfHours * 60 * 60 * 1000);

      return this.moment(date).format('HH:mm:ss');
    },
    setEvents(routines) {
      const app = this;
      const events = [];
      routines.forEach(routine => {
        const rep = (routine.rep) ? `${routine.rep.name}` : '';
        const customer = (routine.customer) ? `${routine.customer.business_name}` : '';

        const eachEvent = {
          id: routine.id,
          title: `${routine.note} (${customer}) by ${rep}`,
          start: routine.schedule_date,
          end: this.schedule_date,
          startTime: routine.schedule_time,
          endTime: this.addHours(1, new Date(routine.schedule_date + 'T' + routine.schedule_time)),
          // backgroundColor: routine.subject_teacher.subject.color_code,
          textColor: 'white',
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
