<script setup>
import DatePicker from "primevue/datepicker";
import InputText from "primevue/inputtext";
import {computed, ref, watch} from "vue";
import {VueScrollPicker} from "vue-scroll-picker";
import Drawer from "primevue/drawer";
import dayjs from "dayjs";

const props = defineProps({
    invalid: {
        type: Boolean,
        default: false
    },
})

const visibility = ref(false);

const openDrawer = () => {
    visibility.value = !visibility.value;
}

const date = ref('');
const currentYear = ref(new Date().getFullYear());
const currentMonth = ref(1);
const currentDay = ref(1);
const emit = defineEmits(["update:date"]);

const years = computed(() => {
    const currYear = new Date().getFullYear();
    const lastYear = 1900;
    return Array.from({ length: currYear - lastYear + 1 }, (_, index) => lastYear + index).reverse();
});

const months = computed(() => Array.from({ length: 12 }, (_, index) => index + 1));

const days = computed(() => {
    const lastDay = new Date(currentYear.value, currentMonth.value, 0).getDate();
    return Array.from({ length: lastDay }, (_, index) => index + 1);
});

const formattedDate = computed(() => {
    return `${currentYear.value}-${String(currentMonth.value).padStart(2, '0')}-${String(currentDay.value).padStart(2, '0')}`;
});

watch(date, (newDate) => {
    if (newDate) {
        emit("update:date", dayjs(newDate).format('YYYY-MM-DD'));
    }
});

watch([currentYear, currentMonth, currentDay], () => {
    const newFormattedDate = formattedDate.value;
    date.value = newFormattedDate;
    emit("update:date", newFormattedDate);
});
</script>

<template>
    <DatePicker
        v-model="date"
        class="w-full hidden md:block"
        dateFormat="yy-mm-dd"
        placeholder="YYYY-MM-DD"
        :invalid="invalid"
    />

    <div class="w-full md:hidden flex">
        <InputText
            @click="openDrawer"
            placeholder="YYYY-MM-DD"
            v-model="date"
            :invalid="invalid"
        />
    </div>

    <Drawer
        v-model:visible="visibility"
        :header="$t('public.select_dob')"
        class="w-full md:max-w-[360px] pb-10 !h-2/5"
        position="bottom"
    >
        <div class="flex w-full">
            <VueScrollPicker :options="years" v-model="currentYear" />
            <VueScrollPicker :options="months" v-model="currentMonth" />
            <VueScrollPicker :options="days" v-model="currentDay" />
        </div>
    </Drawer>
</template>

