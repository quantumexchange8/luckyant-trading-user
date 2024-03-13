<script setup>
import {onMounted, ref, watchEffect} from 'vue';
import Chart from 'chart.js/auto';
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    meta_login: Number
})

const date = ref('');
const chartData = ref({
    labels: [],
    datasets: [],
});
let chartInstance = null;

const fetchData = async () => {
    try {
        const ctx = document.getElementById('masterTradeChart');

        const response = await axios.get('/trading/getMasterTradeChart/' + props.meta_login);
        const {labels, datasets} = response.data;

        chartData.value.labels = labels;
        chartData.value.datasets = datasets;

        if (chartInstance) {
            // Update chart data instead of destroying and recreating
            chartInstance.data = chartData.value;
            chartInstance.update();
        } else {
            chartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: chartData.value,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            display: true,
                            labels: {
                                font: {
                                    family: 'Inter, sans',
                                    size: 12,
                                    weight: 'normal',
                                },
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxHeight: 6,
                                color: '#8695aa'
                            },
                        },
                    },
                },
            });
        }
    } catch (error) {
        console.error('Error fetching data:', error);
    }
};

onMounted(() => {
    fetchData();
});

</script>

<template>
    <div class="h-60">
        <canvas id="masterTradeChart"></canvas>
    </div>
</template>
